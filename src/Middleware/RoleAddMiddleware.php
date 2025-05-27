<?php

declare(strict_types=1);

namespace Pi\User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Pi\Core\Handler\ErrorHandler;
use Pi\User\Service\RoleService;
use Pi\User\Validator\RoleNameValidator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoleAddMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var RoleService */
    protected RoleService $roleService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        RoleService              $roleService,
        ErrorHandler             $errorHandler,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->roleService     = $roleService;
        $this->errorHandler    = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        $title = new Input('title');
        $title->getValidatorChain()->attach(new NotEmpty);

        $name = new Input('name');
        $name->getValidatorChain()->attach(new RoleNameValidator($this->roleService, ['format' => 'strict']));

        $section = new Input('section');
        $section->getValidatorChain()->attach(new InArray(['haystack' => ['api', 'admin']]));

        $inputFilter = new InputFilter();
        $inputFilter->add($title);
        $inputFilter->add($name);
        $inputFilter->add($section);
        $inputFilter->setData($requestBody);
        if (!$inputFilter->isValid()) {
            $message = [];
            foreach ($inputFilter->getInvalidInput() as $error) {
                $message[$error->getName()] = $error->getName() . ': ' . implode(', ', $error->getMessages());
            }

            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_BAD_REQUEST);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => implode(', ', $message),
                    'code'    => StatusCodeInterface::STATUS_BAD_REQUEST,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        return $handler->handle($request);
    }
}