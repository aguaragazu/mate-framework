<?php

namespace Mate\Tests\Validation;

use Mate\Validation\Exceptions\RuleParseException;
use Mate\Validation\Exceptions\UnknownValidationRule;
use Mate\Validation\Rule;
use Mate\Validation\Rules\Email;
use Mate\Validation\Rules\LessThan;
use Mate\Validation\Rules\Nullable;
use Mate\Validation\Rules\Number;
use Mate\Validation\Rules\Required;
use Mate\Validation\Rules\RequiredWhen;
use Mate\Validation\Rules\RequiredWith;
use PHPUnit\Framework\TestCase;

class RuleParseTest extends TestCase {
    protected function setUp(): void {
        Rule::loadDefaultRules();
    }

    public function basicRules() {
        return [
            [Email::class, "email"],
            [Nullable::class, "nullable"],
            [Required::class, "required"],
            [Number::class, "number"],
        ];
    }

    /**
     * @dataProvider basicRules
     */
    public function testParseBasicRules($class, $name) {
        $this->assertInstanceOf($class, Rule::from($name));
    }

    public function testParsingUnknownRulesThrowsUnkownRuleException() {
        $this->expectException(UnknownValidationRule::class);
        Rule::from("unknown");
    }

    public function rulesWithParameters() {
        return [
            [new LessThan(5), "less_than:5"],
            [new RequiredWith("other"), "required_with:other"],
            [new RequiredWhen("other", "=", "test"), "required_when:other,=,test"],
        ];
    }

    /**
     * @dataProvider rulesWithParameters
     */
    public function testParseRulesWithParameters($expected, $rule) {
        $this->assertEquals($expected, Rule::from($rule));
    }

    public function rulesWithParametersWithError() {
        return [
            ["less_than"],
            ["less_than:"],
            ["required_with:"],
            ["required_when"],
            ["required_when:"],
            ["required_when:other"],
            ["required_when:other,"],
            ["required_when:other,="],
            ["required_when:other,=,"],
        ];
    }

    /**
     * @dataProvider rulesWithParametersWithError
     */
    public function testParsingRuleWithParametersWithoutPassingCorrectParametersThrowsRuleParseException($rule) {
        $this->expectException(RuleParseException::class);
        Rule::from($rule);
    }
}
