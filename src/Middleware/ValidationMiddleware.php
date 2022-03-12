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
use User\Validator\EmailValidator;
use User\Validator\IdentityValidator;
use User\Validator\NameValidator;
use User\Validator\PasswordValidator;

class ValidationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public array $validationResult
        = [
            'status'  => true,
            'code'    => StatusCodeInterface::STATUS_OK,
            'message' => '',
        ];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
        ErrorHandler $errorHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->errorHandler    = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get information from request
        $routeMatch = $request->getAttribute('Laminas\Router\RouteMatch');
        $parsedBody = $request->getParsedBody();
        $account    = $request->getAttribute('account');

        // Check parsedBody
        switch ($routeMatch->getMatchedRouteName()) {
            case 'api/login':
                $this->loginIsValid($parsedBody);
                break;

            case 'api/register':
                $this->registerIsValid($parsedBody);
                break;

            case 'api/edit':
                $this->editIsValid($parsedBody, $account);
                break;

            case 'api/password':
                $this->passwordIsValid($parsedBody, $account);
                break;
        }

        // Check if validation result is not true
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

    protected function loginIsValid($params)
    {
        $identity = new Input('identity');
        $identity->getValidatorChain()->attach(new IdentityValidator($this->accountService, ['check_duplication' => false]));

        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator());

        $inputFilter = new InputFilter();
        $inputFilter->add($identity);
        $inputFilter->add($credential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function registerIsValid($params)
    {
        $email = new Input('email');
        $email->getValidatorChain()->attach(new EmailValidator($this->accountService));

        $name = new Input('name');
        $name->getValidatorChain()->attach(new NameValidator($this->accountService));

        $identity = new Input('identity');
        $identity->getValidatorChain()->attach(new IdentityValidator($this->accountService));

        $credential = new Input('credential');
        $credential->getValidatorChain()->attach(new PasswordValidator());

        $inputFilter = new InputFilter();
        $inputFilter->add($email);
        $inputFilter->add($name);
        $inputFilter->add($identity);
        $inputFilter->add($credential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function editIsValid($params, $account)
    {
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

        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }

    protected function passwordIsValid($params, $account)
    {
        $currentCredential = new Input('current_credential');
        $currentCredential->getValidatorChain()->attach(new PasswordValidator());

        $newCredential = new Input('new_credential');
        $newCredential->getValidatorChain()->attach(new PasswordValidator());

        $inputFilter = new InputFilter();
        $inputFilter->add($currentCredential);
        $inputFilter->add($newCredential);
        $inputFilter->setData($params);

        if (!$inputFilter->isValid()) {
            return $this->setErrorHandler($inputFilter);
        }
    }
}