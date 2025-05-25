<?php

declare(strict_types=1);

namespace Pi\User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\ConfigService;
use Pi\Core\Service\UtilityService;
use Pi\User\Service\AccountService;
use Pi\User\Validator\EmailValidator;
use Pi\User\Validator\IdentityValidator;
use Pi\User\Validator\MobileValidator;
use Pi\User\Validator\NameValidator;
use Pi\User\Validator\OtpValidator;
use Pi\User\Validator\PasswordValidator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function sprintf;

class ValidationMiddleware implements MiddlewareInterface
{
    public array $validationResult
        = [
            'status'  => true,
            'code'    => StatusCodeInterface::STATUS_OK,
            'message' => '',
        ];
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var ConfigService */
    protected ConfigService $configService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
        UtilityService           $utilityService,
        CacheService             $cacheService,
        ConfigService            $configService,
        ErrorHandler             $errorHandler,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->utilityService  = $utilityService;
        $this->cacheService    = $cacheService;
        $this->configService   = $configService;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get information from request
        $routeMatch  = $request->getAttribute('Laminas\Router\RouteMatch');
        $parsedBody  = $request->getParsedBody();
        $account     = $request->getAttribute('account');
        $routeParams = $routeMatch->getParams();

        // Check parsedBody
        switch ($routeParams['validator']) {
            case 'login':
                $this->loginIsValid($parsedBody);
                break;

            case 'add':
                $this->registerIsValid($parsedBody);
                break;

            case 'edit':
                $this->editIsValid($parsedBody, $account);
                break;

            case 'device-token':
                $this->deviceTokenIsValid($parsedBody, $account);
                break;

            case 'password-add':
                $this->passwordAddIsValid($parsedBody, $account);
                break;

            case 'password-update':
                $this->passwordEditIsValid($parsedBody);
                break;

            case 'password-admin':
                $this->passwordAdminIsValid($parsedBody);
                break;

            case 'email-request':
                $this->emailRequestIsValid($parsedBody);
                break;

            case 'email-verify':
                $this->emailVerifyIsValid($parsedBody);
                break;

            case 'mobile-request':
                $this->mobileRequestIsValid($parsedBody);
                break;

            case 'mobile-verify':
                $this->mobileVerifyIsValid($parsedBody);
                break;

            case 'name-format':
                $this->nameFormat($parsedBody);
                break;

            default:
                $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
                $request = $request->withAttribute(
                    'error',
                    [
                        'message' => 'Validator not set !',
                        'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                    ]
                );
                return $this->errorHandler->handle($request);
                break;
        }

        // Check if validator result is not true
        if (!$this->validationResult['status']) {
            $request = $request->withAttribute('status', $this->validationResult['code']);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $this->validationResult['message'],
                    'code'    => $this->validationResult['code'],
                ]
            );
            return $this->errorHandler->handle($request);
        }

        return $handler->handle($request);
    }

    private function setErrorHandler($inputFilter): void
    {
        $message = [];
        foreach ($inputFilter->getInvalidInput() as $error) {
            $message[$error->getName()] = $error->getName() . ': ' . implode(', ', $error->getMessages());
        }

        $this->validationResult = [
            'status'  => false,
            'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
            'message' => implode(', ', $message),
        ];
    }

    private function loginIsValid($params): void
    {
        $inputFilter = new InputFilter();

        // Check email or identity or mobile
        if (isset($params['email']) && !empty($params['email'])) {
            // Set input filter
            $email = new Input('email');
            $email->getValidatorChain()->attach(new EmailValidator($this->accountService, ['check_duplication' => false]));
            $inputFilter->add($email);

            // Set check params
            $checkParams = ['email' => $params['email']];
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            // Set input filter
            $identity = new Input('identity');
            $identity->getValidatorChain()->attach(new IdentityValidator($this->accountService, ['check_duplication' => false]));
            $inputFilter->add($identity);

            // Set check params
            $checkParams = ['identity' => $params['identity']];
        } elseif (isset($params['mobile']) && !empty($params['mobile'])) {
            // Set input filter
            $option = [
                'check_duplication' => false,
                'country'           => $option['country'] ?? 'IR',
            ];
            $mobile = new Input('mobile');
            $mobile->getValidatorChain()->attach(new MobileValidator($this->accountService, $option));
            $inputFilter->add($mobile);

            // Set check params
            $checkParams = ['mobile' => $params['mobile']];
        } else {
            $this->validationResult = [
                'status'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => 'Login fields not set !',
            ];
            return;
        }

        // Check credential
        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $this->utilityService, $this->configService));
        $inputFilter->add($credential);

        // Set data and check
        $inputFilter->setData($params);
        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function registerIsValid($params): void
    {
        // Set name
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            $params['name'] = sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        $inputFilter = new InputFilter();

        // Check name
        if (isset($params['name']) && !empty($params['name'])) {
            $name = new Input('name');
            $name->getValidatorChain()->attach(new NameValidator($this->accountService));
            $inputFilter->add($name);
        }

        // Check email
        if (isset($params['email']) && !empty($params['email'])) {
            $email = new Input('email');
            $email->getValidatorChain()->attach(new EmailValidator($this->accountService));
            $inputFilter->add($email);
        }

        // Check credential
        if (isset($params['identity']) && !empty($params['identity'])) {
            $identity = new Input('identity');
            $identity->getValidatorChain()->attach(new IdentityValidator($this->accountService));
            $inputFilter->add($identity);
        }

        // Check identity
        if (isset($params['credential']) && !empty($params['credential'])) {
            $credential = new Input('credential');
            $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $this->utilityService, $this->configService));
            $inputFilter->add($credential);
        }

        // Check mobile
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $option = [
                'check_duplication' => true,
                'country'           => 'IR',
            ];
            if (isset($params['country']) && !empty($params['country'])) {
                $option['country'] = $params['country'];
            }

            $mobile = new Input('mobile');
            $mobile->getValidatorChain()->attach(new MobileValidator($this->accountService, $option));
            $inputFilter->add($mobile);
        }

        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function editIsValid($params, $account): void
    {
        // Set name
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            $params['name'] = sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        $inputFilter = new InputFilter();

        if (isset($params['email']) && !empty($params['email'])) {
            $email = new Input('email');
            $email->getValidatorChain()->attach(new EmailValidator($this->accountService, ['user_id' => $account['id']]));
            $inputFilter->add($email);
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $name = new Input('name');
            $name->getValidatorChain()->attach(new NameValidator($this->accountService, ['user_id' => $account['id']]));
            $inputFilter->add($name);
        }

        if (isset($params['identity']) && !empty($params['identity'])) {
            $identity = new Input('identity');
            $identity->getValidatorChain()->attach(new IdentityValidator($this->accountService, ['user_id' => $account['id']]));
            $inputFilter->add($identity);
        }

        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $mobile = new Input('mobile');
            $mobile->getValidatorChain()->attach(new MobileValidator($this->accountService, ['user_id' => $account['id']]));
            $inputFilter->add($mobile);
        }

        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function deviceTokenIsValid($params, $account): void
    {
        if (!isset($params['device_token']) || empty($params['device_token']) || !is_string($params['device_token'])) {
            $this->validationResult = [
                'status'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => 'Device token was not set or its wrong !',
            ];
        }
    }

    private function passwordAddIsValid($params, $account): void
    {
        // Set option
        $option = [
            'user_id'            => $params['user_id'] ?? $account['id'],
            'check_has_password' => 1,
        ];

        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $this->utilityService, $this->configService, $option));

        $inputFilter = new InputFilter();
        $inputFilter->add($credential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function passwordEditIsValid($params): void
    {
        $option = [
            'check_strong' => 0,
        ];

        $currentCredential = new Input('current_credential');
        $currentCredential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $this->utilityService, $this->configService, $option));

        $newCredential = new Input('new_credential');
        $newCredential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $this->utilityService, $this->configService));

        $inputFilter = new InputFilter();
        $inputFilter->add($currentCredential);
        $inputFilter->add($newCredential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function passwordAdminIsValid($params): void
    {
        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $this->utilityService, $this->configService));

        $inputFilter = new InputFilter();
        $inputFilter->add($credential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function emailRequestIsValid($params): void
    {
        $option = [
            'check_duplication' => false,
        ];

        $email = new Input('email');
        $email->getValidatorChain()->attach(new EmailValidator($this->accountService, $option));

        $inputFilter = new InputFilter();
        $inputFilter->add($email);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function emailVerifyIsValid($params): void
    {
        $option = [
            'check_duplication' => false,
        ];

        // Check email
        $email = new Input('email');
        $email->getValidatorChain()->attach(new EmailValidator($this->accountService, $option));

        $option = [
            'email' => $params['email'],
        ];

        // Check otp
        $otp = new Input('otp');
        $otp->getValidatorChain()->attach(new OtpValidator($this->accountService, $this->cacheService, $option));

        $inputFilter = new InputFilter();
        $inputFilter->add($email);
        $inputFilter->add($otp);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function mobileRequestIsValid($params): void
    {
        $option = [
            'check_duplication' => false,
            'country'           => 'IR',
        ];
        if (isset($params['country']) && !empty($params['country'])) {
            $option['country'] = $params['country'];
        }

        $mobile = new Input('mobile');
        $mobile->getValidatorChain()->attach(new MobileValidator($this->accountService, $option));

        $inputFilter = new InputFilter();
        $inputFilter->add($mobile);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function mobileVerifyIsValid($params): void
    {
        $inputFilter = new InputFilter();

        $option = [
            'check_duplication' => false,
            'country'           => 'IR',
        ];
        if (isset($params['country']) && !empty($params['country'])) {
            $option['country'] = $params['country'];
        }

        $mobile = new Input('mobile');
        $mobile->getValidatorChain()->attach(new MobileValidator($this->accountService, $option));
        $inputFilter->add($mobile);

        $option = [
            'mobile' => $params['mobile'],
        ];

        // Check otp
        $otp = new Input('otp');
        $otp->getValidatorChain()->attach(new OtpValidator($this->accountService, $this->cacheService, $option));
        $inputFilter->add($otp);

        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }

    private function nameFormat($params): void
    {
        $name = new Input('name');
        $name->getValidatorChain()->attach(new NameValidator($this->accountService, ['format' => 'strict']));

        $inputFilter = new InputFilter();
        $inputFilter->add($name);
        $inputFilter->setData($params);
        if (!$inputFilter->isValid()) {
            $this->setErrorHandler($inputFilter);
        }
    }
}