<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use hstanleycrow\EasyPHPFormValidator\Validator;
use hstanleycrow\EasyPHPFormValidator\ValidationException;
use App\Controllers\Admin\Users\UsersFormValidator;

final class UsersFormValidatorTest extends TestCase
{
    private function baseData(): array
    {
        return [
            'name'     => 'Ada Lovelace',
            'username' => 'ada',
            'active'   => 'S',
            'isAdmin'  => 'N',
        ];
    }

    public function test_create_passes_when_password_confirmation_matches(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData() + ['password' => 'secret1', 'password_confirmation' => 'secret1'];

        Validator::validate($data, $validator->getRules(false), $validator->getMessages(false));
        $this->addToAssertionCount(1);
    }

    public function test_create_fails_when_password_confirmation_does_not_match(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData() + ['password' => 'secret1', 'password_confirmation' => 'other'];

        $this->expectException(ValidationException::class);
        Validator::validate($data, $validator->getRules(false), $validator->getMessages(false));
    }

    public function test_edit_does_not_require_password(): void
    {
        $validator = new UsersFormValidator();

        Validator::validate($this->baseData(), $validator->getRules(true), $validator->getMessages(true));
        $this->addToAssertionCount(1);
    }

    public function test_edit_validates_password_when_present(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData() + ['password' => 'abc'];

        $this->expectException(ValidationException::class);
        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
    }

    public function test_edit_passes_with_a_valid_new_password(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData() + ['password' => 'secret1'];

        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
        $this->addToAssertionCount(1);
    }

    public function test_active_accepts_S(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['active'] = 'S';

        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
        $this->addToAssertionCount(1);
    }

    public function test_active_accepts_N(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['active'] = 'N';

        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
        $this->addToAssertionCount(1);
    }

    public function test_active_rejects_value_outside_S_or_N(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['active'] = 'X';

        $this->expectException(ValidationException::class);
        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
    }

    public function test_active_rejects_empty_value(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['active'] = '';

        $this->expectException(ValidationException::class);
        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
    }

    public function test_is_admin_accepts_S(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['isAdmin'] = 'S';

        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
        $this->addToAssertionCount(1);
    }

    public function test_is_admin_accepts_N(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['isAdmin'] = 'N';

        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
        $this->addToAssertionCount(1);
    }

    public function test_is_admin_rejects_value_outside_S_or_N(): void
    {
        $validator = new UsersFormValidator();
        $data = $this->baseData();
        $data['isAdmin'] = 'X';

        $this->expectException(ValidationException::class);
        Validator::validate($data, $validator->getRules(true), $validator->getMessages(true));
    }
}
