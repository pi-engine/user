<?php

declare(strict_types=1);

namespace Pi\User\Middleware;

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Imagine\Gd\Imagine;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\UploadFile;
use Pi\Core\Handler\ErrorHandler;
use Pi\User\Service\AccountService;
use Pi\User\Service\AvatarService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AvatarUploadMiddleware implements MiddlewareInterface
{
    public array $validationResult
        = [
            'status'  => true,
            'code'    => StatusCodeInterface::STATUS_OK,
            'message' => '',
            'key'     => '',
        ];

    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var AvatarService */
    protected AvatarService $avatarService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
        AvatarService            $avatarService,
        ErrorHandler             $errorHandler,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->avatarService   = $avatarService;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uploadFiles = $request->getUploadedFiles();

        // Check valid
        $this->attacheIsValid($uploadFiles);

        // Check if validation result is not true
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

        // Create an Imagine instance
        $imagine = new Imagine();
        foreach ($uploadFiles as $uploadFile) {
            try {
                // Open the uploaded file with Imagine
                $image = $imagine->open($uploadFile->getStream()->getMetadata('uri'));

                // Check if the image is square
                $size = $image->getSize();
                if ($size->getWidth() !== $size->getHeight()) {
                    $request = $request->withAttribute('status', 422);
                    $request = $request->withAttribute(
                        'error',
                        [
                            'message' => "Image must be square. The size is : {$size->getWidth()}*{$size->getHeight()}",
                            'code'    => 422,
                        ]
                    );
                    return $this->errorHandler->handle($request);
                }

                // Check if the image size is greater than 512x512
                if ($size->getWidth() < 512 || $size->getHeight() < 512) {
                    $request = $request->withAttribute('status', 422);
                    $request = $request->withAttribute(
                        'error',
                        [
                            'message' => "Image size must be at least 512x512 pixels.. The size is : {$size->getWidth()}*{$size->getHeight()}",
                            'code'    => 422,
                        ]
                    );
                    return $this->errorHandler->handle($request);
                }
            } catch (Exception $e) {
                $request = $request->withAttribute('status', 422);
                $request = $request->withAttribute(
                    'error',
                    [
                        'message' => 'Invalid image.' . $e->getMessage(),
                        'code'    => 422,
                    ]
                );
                return $this->errorHandler->handle($request);
            }
        }

        return $handler->handle($request);
    }

    protected function setErrorHandler($inputFilter): array
    {
        $message = [];
        foreach ($inputFilter->getMessages() as $key => $value) {
            $message[$key] = $value;
        }

        return $this->validationResult = [
            'status'  => false,
            'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
            'message' => implode(', ', $message),
        ];
    }

    protected function attacheIsValid($uploadFiles)
    {
        $validatorUpload    = new UploadFile();
        $validatorExtension = new Extension($this->config['allowed_extension']);
        $validatorMimeType  = new MimeType($this->config['mime_type']);
        $validatorSize      = new Size($this->config['allowed_size']);

        // Check attached files
        foreach ($uploadFiles as $uploadFile) {
            if (!$validatorUpload->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorUpload);
            }
            if (!$validatorExtension->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorExtension);
            }
            /* if (!$validatorMimeType->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorMimeType);
            } */
            if (!$validatorSize->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorUpload);
            }
        }
    }
}