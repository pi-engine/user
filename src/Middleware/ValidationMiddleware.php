<?php

namespace User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;
use User\Service\AccountService;
use User\Service\CacheService;
use User\Validator\EmailValidator;
use User\Validator\IdentityValidator;
use User\Validator\MobileValidator;
use User\Validator\NameValidator;
use User\Validator\OtpValidator;
use User\Validator\PasswordValidator;
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

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
        CacheService $cacheService,
        ErrorHandler $errorHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->cacheService    = $cacheService;
        $this->errorHandler    = $errorHandler;
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

            case 'password-add':
                $this->passwordAddIsValid($parsedBody, $account);
                break;

            case 'password-update':
                $this->passwordEditIsValid($parsedBody, $account);
                break;

            case 'mobile':
                $this->mobileIsValid($parsedBody);
                break;

            case 'email':
                $this->emailIsValid($parsedBody);
                break;

            case 'verify-mobile':
                $this->loginMobileIsValid($parsedBody);
                break;

            case 'verify-email':
                $this->loginEmailIsValid($parsedBody);
                break;

            default:
                $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
                $request = $request->withAttribute('error',
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
            $request = $request->withAttribute('error',
                [
                    'message' => $this->validationResult['message'],
                    'code'    => $this->validationResult['code'],
                ]
            );
            return $this->errorHandler->handle($request);
        }

        return $handler->handle($request);
    }

    protected function loginIsValid($params)
    {
        $inputFilter = new InputFilter();

        // Check email or identity or mobile
        if (isset($params['email']) && !empty($params['email'])) {
            $email = new Input('email');
            $email->getValidatorChain()->attach(new EmailValidator($this->accountService, ['check_duplication' => false]));
            $inputFilter->add($email);
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            $identity = new Input('identity');
            $identity->getValidatorChain()->attach(new IdentityValidator($this->accountService, ['check_duplication' => false]));
            $inputFilter->add($identity);
        } elseif (isset($params['mobile']) && !empty($params['mobile'])) {
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
        } else {
            return $this->validationResult = [
                'status'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => 'Login fields not set !',
            ];
        }

        // Check credential
        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService));
        $inputFilter->add($credential);

        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function setErrorHandler($inputFilter): array
    {
        $message = [];
        foreach ($inputFilter->getInvalidInput() as $error) {
            $message[$error->getName()] = implode(', ', $error->getMessages());
        }

        return $this->validationResult = [
            'status'  => false,
            'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
            'message' => $message,
        ];
    }

    protected function registerIsValid($params)
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
            $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService));
            $inputFilter->add($credential);
        }

        // Check mobile
        if (isset($params['mobile']) && !empty($params['mobile'])) {
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
        }

        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function editIsValid($params, $account)
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
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function passwordAddIsValid($params, $account)
    {
        // Set option
        $option = [
            'user_id'            => $account['id'],
            'check_has_password' => 1,
        ];

        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator($this->accountService, $option));

        $inputFilter = new InputFilter();
        $inputFilter->add($credential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function passwordEditIsValid($params, $account)
    {
        $currentCredential = new Input('current_credential');
        $currentCredential->getValidatorChain()->attach(new PasswordValidator($this->accountService));

        $newCredential = new Input('new_credential');
        $newCredential->getValidatorChain()->attach(new PasswordValidator($this->accountService));

        $inputFilter = new InputFilter();
        $inputFilter->add($currentCredential);
        $inputFilter->add($newCredential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function mobileIsValid($params)
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
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function emailIsValid($params)
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
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function loginMobileIsValid($params)
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
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function loginEmailIsValid($params)
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
            return $this->setErrorHandler($inputFilter);
        }
    }

}