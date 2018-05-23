<?php
/**
 * Created by PhpStorm.
 * User: I.Kapelyushny
 * Date: 23.05.2018
 * Time: 12:38
 */

namespace M1naret\GraphQL\Error;

use GraphQL\Error\Error as BaseError;
use Illuminate\Support\MessageBag;
use M1naret\GraphQL\Error\Error as GraphQLError;

/**
 * Class ErrorFormatter
 *
 * @package M1naret\GraphQL\Error
 */
class ErrorFormatter
{
    /**
     * @var BaseError
     */
    static private $error;

    /**
     * @var BaseError|GraphQLError|ValidationError
     */
    static private $previous;

    /**
     * @param BaseError $error
     * @return array
     */
    public static function format(BaseError $error): array
    {
        self::$error = $error;
        self::$previous = $error->getPrevious();

        $formatted = [
            'code' => self::getCode(),
        ];

        if (self::$previous && self::$previous instanceof ValidationError) {
            $formatted += ['validation_errors' => self::getValidationErrors()];
        } else {
            $formatted += ['message' => self::getMessage(),];
            if (self::$previous && self::$previous instanceof GraphQLError) {
                $formatted += ['headers' => self::$previous->getHeaders(),];
            }
        }

        return $formatted;
    }

    /**
     * @return int
     */
    private static function getCode() : int
    {
        return (int)(self::$previous ? self::$previous->getCode() : self::$error->getCode() ?: 0);
    }

    /**
     * @return string
     */
    private static function getMessage(): string
    {
        return self::$previous ? self::$previous->getMessage() : self::$error->getMessage() ?: '';
    }

    /**
     * @return array
     */
    protected static function getValidationErrors(): array
    {
        /** @var MessageBag $validationErrors */
        $validationErrors = self::$previous->getValidatorMessages();
        $formattedErrors = [];
        foreach ($validationErrors->getMessages() as $field => $errors) {
            $formattedErrors[] = [
                'field' => $field,
                'messages' => $errors,
            ];
        }

        return $formattedErrors;
    }
}