<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;

final readonly class OpenAi
{
    /**
     * A strict JSON schema response format.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public static function schema(string $name, array $schema): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $name,
                'strict' => true,
                'schema' => $schema,
            ],
        ];
    }

    /**
     * A strict object schema: every property is required and nothing else is allowed.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function object(array $properties): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => array_keys($properties),
            'properties' => $properties,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public static function nullable(array $schema): array
    {
        return ['anyOf' => [['type' => 'null'], $schema]];
    }

    /**
     * @param  list<string>  $values
     * @return array<string, mixed>
     */
    public static function enum(array $values): array
    {
        return ['type' => 'string', 'enum' => $values];
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    public static function list(array $items): array
    {
        return ['type' => 'array', 'items' => $items];
    }

    /**
     * Asks the model for a JSON answer that follows the given response format.
     *
     * @param  array<string, mixed>  $responseFormat
     * @return array<string, mixed>
     *
     * @throws ConnectionException|RequestException|JsonException
     */
    public function structured(string $instructions, string $input, array $responseFormat): array
    {
        $response = Http::withToken(config()->string('services.openai.key'))
            ->baseUrl(config()->string('services.openai.url'))
            ->timeout(config()->integer('analysis.openai.timeout'))
            ->acceptJson()
            ->post('chat/completions', [
                'model' => config()->string('analysis.openai.model'),
                'temperature' => config()->float('analysis.openai.temperature'),
                'response_format' => $responseFormat,
                'messages' => [
                    ['role' => 'system', 'content' => $instructions],
                    ['role' => 'user', 'content' => $input],
                ],
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');

        $decoded = json_decode(is_string($content) ? $content : '', true, 512, JSON_THROW_ON_ERROR);

        throw_unless(is_array($decoded), JsonException::class, 'The response is not a JSON object.');

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
