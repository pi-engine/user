<?php

namespace User\Factory\Service;

use Interop\Container\ContainerInterface;
use Laminas\I18n\Translator\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TranslatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Translator
    {
        $config = $container->get('config');

        $translator = new Translator();
        $translator->setLocale($config['translator']['locale']);

        foreach ($config['translator']['translation_file_patterns'] as $pattern) {
            $translator->addTranslationFilePattern(
                $pattern['type'],
                $pattern['base_dir'],
                $pattern['pattern']
            );
        }

        return $translator;
    }
}