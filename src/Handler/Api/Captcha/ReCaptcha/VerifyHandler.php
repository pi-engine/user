<?php

namespace User\Handler\Api\Captcha\ReCaptcha;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\ReCaptcha\ReCaptcha;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->config          = $config;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        // Set result
        $result = [
            'result' => false,
            'data'   => [],
            'error'  => [
                'message' => 'Unable to verify the captcha',
            ],
            'status' => StatusCodeInterface::STATUS_FORBIDDEN,
        ];

        // Check params
        if (isset($requestBody['g-recaptcha-response']) && !empty($requestBody['g-recaptcha-response'])) {
            // Verify captcha
            $recaptcha = new ReCaptcha($this->config['recaptcha']['public'], $this->config['recaptcha']['secret']);
            $verify    = $recaptcha->verify($requestBody['g-recaptcha-response']);
            if ($verify->isValid()) {
                $result = [
                    'result' => true,
                    'data'   => [
                        'message' => 'The captcha verified successfully !',
                    ],
                    'error'  => [],
                ];
            } else {
                $result['error']['message'] = implode(',', $verify->getErrorCodes());
            }
        }

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}