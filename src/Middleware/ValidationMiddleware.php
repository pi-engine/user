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
        AccountService $accountService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->errorHandler    = new ErrorHandler($this->responseFactory, $this->streamFactory);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get information from request
        $routeMatch = $request->getAttribute('Laminas\Router\RouteMatch');
        $parsedBody = $request->getParsedBody();

        // Check parsedBody
        switch ($routeMatch->getMatchedRouteName()) {
            case 'user/login':
                $this->loginIsValid($parsedBody);
                break;

            case 'user/register':
                $this->registerIsValid($parsedBody);
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

    public function loginIsValid($params)
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
            $message = [];
            foreach ($inputFilter->getInvalidInput() as $error) {
                $message[] = implode(', ', $error->getMessages());
            }

            return $this->validationResult = [
                'status'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => implode(', ', $message),
            ];
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
            $message = [];
            foreach ($inputFilter->getInvalidInput() as $error) {
                $message[] = implode(', ', $error->getMessages());
            }

            return $this->validationResult = [
                'status'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => implode(', ', $message),
            ];
        }
    }
}