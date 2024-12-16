<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Captcha\ReCaptcha;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReCaptcha\ReCaptcha;

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
        StreamFactoryInterface   $streamFactory,
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

        // Call ReCaptcha
        $recaptcha = new ReCaptcha($this->config['recaptcha']['secret']);
        $response  = $recaptcha->setExpectedAction('submit')->verify($requestBody['token'], $_SERVER['REMOTE_ADDR']);

        // Check result
        if ($response->isSuccess() && $response->getScore() > 0.5) {
            $result = [
                'result' => true,
                'data'   => [
                    'message' => 'The captcha verified successfully !',
                    'score'   => $response->getScore(),
                ],
                'error'  => [],
            ];
        } else {
            $result['error'] = [
                'message' => 'Verification failed',
                'code'    => $response->getErrorCodes(),
                'score'   => $response->getScore(),
            ];
        }

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}