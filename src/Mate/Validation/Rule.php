<?php

namespace Mate\Validation;

use Mate\Validation\Exceptions\RuleParseException;
use Mate\Validation\Exceptions\UnknownRuleException;
use Mate\Validation\Rules\Confirmed;
use Mate\Validation\Rules\Email;
use Mate\Validation\Rules\LessThan;
use Mate\Validation\Rules\Max;
use Mate\Validation\Rules\Min;
use Mate\Validation\Rules\Number;
use Mate\Validation\Rules\Required;
use Mate\Validation\Rules\RequiredWhen;
use Mate\Validation\Rules\RequiredWith;
use Mate\Validation\Rules\Unique;
use Mate\Validation\Rules\ValidationRule;
use ReflectionClass;

class Rule
{
    private static array $rules = [];

    private static array $defaultRules = [
        Required::class,
        RequiredWith::class,
        RequiredWhen::class,
        Number::class,
        LessThan::class,
        Email::class,
        Unique::class,
        Confirmed::class,
        Min::class,
        Max::class,
    ];

    public static function loadDefaultRules()
    {
        self::load(self::$defaultRules);
    }

    public static function load(array $rules)
    {
        foreach ($rules as $class) {
            $className = array_slice(explode("\\", $class), -1)[0];
            $ruleName = snake_case($className);
            self::$rules[$ruleName] = $class;
        }
    }

    public static function nameOf(ValidationRule $rule): string
    {
        $class = new ReflectionClass($rule);

        return snake_case($class->getShortName());
    }

    public static function email(): ValidationRule
    {
        return new Email();
    }

    public static function required(): ValidationRule
    {
        return new Required();
    }

    public static function requiredWith(string $withField): ValidationRule
    {
        return new RequiredWith($withField);
    }

    public static function number(): ValidationRule
    {
        return new Number();
    }

    public static function lessThan(int|float $value): ValidationRule
    {
        return new LessThan($value);
    }

    public static function unique(string $table, string $column = 'email'): ValidationRule
    {
        return new Unique($table, $column);
    }

    public static function confirmed(): ValidationRule
    {
        return new Confirmed();
    }

    public static function min(int $length): ValidationRule
    {
        return new Min($length);
    }

    public static function max(int $length): ValidationRule
    {
        return new Max($length);
    }

    public static function requiredWhen(
        string $otherField,
        string $operator,
        int|float $value
    ): ValidationRule {
        return new RequiredWhen($otherField, $operator, $value);
    }

    public static function parseBasicRule(string $ruleName): ValidationRule
    {
        $class = new ReflectionClass(self::$rules[$ruleName]);

        if (count($class->getConstructor()?->getParameters() ?? []) > 0) {
            throw new RuleParseException("Rule $ruleName requires parameters, but none have been passed");
        }

        return $class->newInstance();
    }

    public static function parseRuleWithParameters(string $ruleName, string $params): ValidationRule
    {
        $class = new ReflectionClass(self::$rules[$ruleName]);
        $constructorParameters = $class->getConstructor()?->getParameters() ?? [];
        $givenParameters = array_filter(explode(",", $params), fn ($p) => !empty($p));

        if (count($givenParameters) !== count($constructorParameters)) {
            throw new RuleParseException(sprintf(
                "Rule %s requires %d parameters, but %d where given: %s",
                $ruleName,
                count($constructorParameters),
                count($givenParameters),
                $params
            ));
        }

        return $class->newInstance(...$givenParameters);
    }

    public static function from(string $str): ValidationRule
    {
        
        if (strlen($str) == 0) {
            throw new RuleParseException("Can't parse empty string to rule");
        }

        $ruleParts = explode(":", $str);

        if (!array_key_exists($ruleParts[0], self::$rules)) {
            throw new UnknownRuleException("Rule {$ruleParts[0]} not found");
        }

        if (count($ruleParts) == 1) {
            return self::parseBasicRule($ruleParts[0]);
        }

        [$ruleName, $params] = $ruleParts;

        return self::parseRuleWithParameters($ruleName, $params);
    }
}
