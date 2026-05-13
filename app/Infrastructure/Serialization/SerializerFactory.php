<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerFactory
{
    public static function create(): SerializerInterface
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        return new Serializer(
            [
                new DateTimeNormalizer(),
                new BackedEnumNormalizer(),
                new JsonSerializableNormalizer(),
                new ArrayDenormalizer(),
                new ObjectNormalizer(
                    nameConverter: new CamelCaseToSnakeCaseNameConverter(),
                    propertyTypeExtractor: new PropertyInfoExtractor(
                        listExtractors: [$reflectionExtractor],
                        typeExtractors: [$phpDocExtractor, $reflectionExtractor],
                    ),
                    defaultContext: [
                        ObjectNormalizer::SKIP_NULL_VALUES => true,
                    ]
                )
            ],
            [
                new JsonEncode(),
            ]
        );
    }
}
