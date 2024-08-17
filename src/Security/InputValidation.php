<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Filter\HtmlEntities;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\I18n\Validator\IsFloat;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Date;
use Laminas\Validator\Digits;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Ip;
use Laminas\Validator\IsJsonString;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uri;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class InputValidation implements SecurityInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var InputFilter */
    protected InputFilter $inputFilter;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'inputValidation';

    protected string $message = 'Access denied: Input data not valid!';

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->config          = $config;
        $this->inputFilter = new InputFilter();
    }

    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['inputValidation']['ignore_whitelist'] === true
            && isset($securityStream['ip']['data']['in_whitelist'])
            && (bool)$securityStream['ip']['data']['in_whitelist'] === true
        ) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'ignore',
                'data'   => [],
            ];
        }

        // Get request and query body
        $requestParams = $request->getParsedBody();
        $QueryParams   = $request->getQueryParams();

        // Do check
        $params = array_merge($requestParams, $QueryParams);
        if (!empty($params)) {
            $this->processData($params);
            $this->inputFilter->setData($params);
            if (!$this->inputFilter->isValid()) {

                // Set error message
                $this->setErrorMessage($this->inputFilter->getMessages());

                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [
                        'message' => $this->inputFilter->getMessages()
                    ],
                ];
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    public function setErrorMessage($messages): void
    {
        if (is_string($messages)) {
            $this->message = $this->message . ' ' . $messages;
        } else {
            $errorMessage = [];
            foreach ($messages as $field => $filedMessage) {
                if (is_array($filedMessage)) {
                    foreach ($filedMessage as $subField => $subFiledMessage) {
                        $errorMessage[$subField] = $subFiledMessage;
                    }
                } else {
                    $errorMessage[$field] = $filedMessage;
                }
            }

            $this->message = $this->message . implode(', ', $errorMessage);
        }
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    private function processData(array $data): void
    {
        foreach ($data as $key => $value) {
            $input = new Input($key);

            // Common filters
            $input->getFilterChain()->attach(new StringTrim());
            $input->getFilterChain()->attach(new StripTags());
            $input->getFilterChain()->attach(new HtmlEntities());

            // Type-specific validators
            if (is_int($value)) {
                $input->getValidatorChain()->attach(new Digits());
            } elseif (is_float($value)) {
                $input->getValidatorChain()->attach(new IsFloat());
            } elseif (is_string($value)) {
                $input->getValidatorChain()->attach(new StringLength(['min' => 1, 'max' => 65535]));
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $input->getValidatorChain()->attach(new EmailAddress());
                } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                    $input->getValidatorChain()->attach(new Uri());
                } elseif (filter_var($value, FILTER_VALIDATE_IP)) {
                    $input->getValidatorChain()->attach(new Ip());
                } elseif (strtotime($value) !== false) {
                    $input->getValidatorChain()->attach(new Date(['format' => 'Y-m-d']));
                } elseif ($this->isJson($value)) {
                    $input->getValidatorChain()->attach(new IsJsonString());
                }
            } elseif (is_array($value)) {
                $this->processData($value);
                continue;
            } else {
                $input->getValidatorChain()->attach(new NotEmpty(['message' => 'Unrecognized data type']));
            }

            // Add
            $this->inputFilter->add($input);
        }
    }

    private function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}