<?php

namespace Tests\Unit\Domain;

use App\Domain\DocumentNumber;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Role;
use App\Domain\Student;
use App\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserAndStudentTest extends TestCase
{
    public function test_username_is_trimmed_and_lowercased(): void
    {
        $user = User::createAssistant('u1', 'Camila ', 'hash', 'assistant');

        $this->assertSame('camila', $user->username);
        $this->assertSame(Role::Assistant, $user->role);
    }

    public function test_admin_role_cannot_be_requested_through_the_api(): void
    {
        $this->expectException(ValidationFailed::class);
        User::createAssistant('u1', 'camila', 'hash', 'admin');
    }

    public function test_unknown_role_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);
        User::createAssistant('u1', 'camila', 'hash', 'librarian');
    }

    public function test_admin_is_only_provisioned_explicitly(): void
    {
        $admin = User::provisionAdmin('u0', ' Admin', 'hash');

        $this->assertSame('admin', $admin->username);
        $this->assertSame(Role::Admin, $admin->role);
    }

    public function test_username_with_spaces_inside_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);
        User::normalizeUsername('camila rojas');
    }

    public function test_student_name_is_trimmed_and_inner_spaces_collapsed(): void
    {
        $student = Student::register('s1', '  Laura   Gómez ', DocumentNumber::from('1001234567'));

        $this->assertSame('Laura Gómez', $student->fullName);
        $this->assertSame('1001234567', $student->documentNumber->value);
    }

    public function test_student_name_is_required(): void
    {
        $this->expectException(ValidationFailed::class);
        Student::register('s1', '   ', DocumentNumber::from('1001234567'));
    }
}
