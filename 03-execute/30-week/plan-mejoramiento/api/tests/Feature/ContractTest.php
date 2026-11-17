<?php

namespace Tests\Feature;

use App\Infrastructure\Http\ErrorRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\Support\CallsTheApi;
use Tests\Support\ImageBytes;
use Tests\TestCase;

/**
 * T-16: the 21 routes and the 14 business codes of docs/api-contract.md, through HTTP, with the real adapters and
 * PostgreSQL. Every error is checked to have the single body { code, message, details }.
 */
final class ContractTest extends TestCase
{
    use CallsTheApi;
    use RefreshDatabase;

    /** Contract §3, in the order of the document. */
    private const ROUTES = [
        'POST api/v1/auth/login',
        'GET api/v1/health',
        'POST api/v1/users',
        'GET api/v1/categories',
        'GET api/v1/laptops',
        'GET api/v1/laptops/{id}',
        'POST api/v1/laptops',
        'PUT api/v1/laptops/{id}',
        'DELETE api/v1/laptops/{id}',
        'PUT api/v1/laptops/{id}/photo',
        'DELETE api/v1/laptops/{id}/photo',
        'GET api/v1/laptops/{id}/photo',
        'GET api/v1/students',
        'GET api/v1/students/{id}',
        'POST api/v1/students',
        'POST api/v1/loans',
        'GET api/v1/loans',
        'GET api/v1/loans/{id}',
        'POST api/v1/loans/{loan_id}/items/{item_id}/return',
        'GET api/v1/loans/overdue',
        'GET api/v1/reports/most-requested',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->startApi();
    }

    public function test_the_api_exposes_exactly_the_21_routes_of_the_contract(): void
    {
        $registered = [];
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/')) {
                foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                    $registered[] = "{$method} {$route->uri()}";
                }
            }
        }

        $this->assertCount(21, self::ROUTES);
        $this->assertEqualsCanonicalizing(self::ROUTES, $registered);
    }

    public function test_the_renderer_knows_the_14_business_codes_with_their_status(): void
    {
        $this->assertSame([
            'INVALID_CREDENTIALS' => 401, 'FORBIDDEN_ROLE' => 403, 'NOT_FOUND' => 404,
            'USERNAME_TAKEN' => 409, 'MODEL_CODE_TAKEN' => 409, 'DOCUMENT_TAKEN' => 409, 'LAPTOP_HAS_OPEN_LOANS' => 409,
            'LAPTOP_NOT_AVAILABLE' => 409, 'STUDENT_HAS_OVERDUE' => 409, 'ITEM_ALREADY_RETURNED' => 409,
            'STUDENT_LIMIT_EXCEEDED' => 422, 'DUPLICATE_LAPTOP_IN_LOAN' => 422, 'VALIDATION_ERROR' => 422,
            'INVALID_DATE_RANGE' => 422,
        ], ErrorRenderer::STATUS_BY_CODE);
    }

    public function test_login_and_health(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);

        $this->postJson('/api/v1/auth/login', ['username' => ' Admin ', 'password' => self::ADMIN_PASSWORD])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'role'])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('expires_in', 28800)
            ->assertJsonPath('role', 'admin');

        $this->assertErrorBody($this->postJson('/api/v1/auth/login', ['username' => 'admin', 'password' => 'wrong-password']), 401, 'INVALID_CREDENTIALS');
        $this->assertErrorBody($this->postJson('/api/v1/auth/login', ['username' => 'admin']), 422, 'VALIDATION_ERROR');
    }

    public function test_without_a_valid_token_every_protected_route_answers_401(): void
    {
        $this->assertErrorBody($this->getJson('/api/v1/categories'), 401, 'INVALID_CREDENTIALS');
        $this->assertErrorBody($this->getJson('/api/v1/laptops', ['Authorization' => 'Bearer not-a-token']), 401, 'INVALID_CREDENTIALS');

        $token = $this->adminToken();
        $this->clock->advance('+8 hours +1 second');
        $this->assertErrorBody($this->send($token, 'GET', '/categories'), 401, 'INVALID_CREDENTIALS');
    }

    public function test_assistant_gets_403_on_admin_routes_without_learning_if_the_resource_exists(): void
    {
        $assistant = $this->assistantToken();
        $missing = '9a1e6c64-7c6f-4d0f-9a43-2f1a0f6d1e01';

        $this->assertErrorBody($this->send($assistant, 'POST', '/users', ['username' => 'otro', 'password' => 'x', 'role' => 'assistant']), 403, 'FORBIDDEN_ROLE');
        $this->assertErrorBody($this->send($assistant, 'POST', '/laptops', []), 403, 'FORBIDDEN_ROLE');
        $this->assertErrorBody($this->send($assistant, 'DELETE', "/laptops/{$missing}"), 403, 'FORBIDDEN_ROLE');
        $this->assertErrorBody($this->send($assistant, 'GET', '/reports/most-requested?from=2026-11-01&to=2026-11-30'), 403, 'FORBIDDEN_ROLE');
    }

    public function test_users_are_created_as_assistants_and_never_return_the_password(): void
    {
        $admin = $this->adminToken();

        $response = $this->send($admin, 'POST', '/users', ['username' => 'Camila ', 'password' => self::ASSISTANT_PASSWORD, 'role' => 'assistant'])
            ->assertCreated()
            ->assertJsonPath('username', 'camila')
            ->assertJsonPath('role', 'assistant');
        $this->assertSame(['id', 'username', 'role'], array_keys($response->json()));
        $this->assertStringNotContainsString(self::ASSISTANT_PASSWORD, $response->getContent());
        $this->assertStringNotContainsString('password', $response->getContent());

        $this->assertErrorBody($this->send($admin, 'POST', '/users', ['username' => 'camila', 'password' => self::ASSISTANT_PASSWORD, 'role' => 'assistant']), 409, 'USERNAME_TAKEN');
        $this->assertErrorBody($this->send($admin, 'POST', '/users', ['username' => 'jefe', 'password' => self::ASSISTANT_PASSWORD, 'role' => 'admin']), 422, 'VALIDATION_ERROR');
        $this->assertErrorBody($this->send($admin, 'POST', '/users', ['username' => 'corto', 'password' => '1234567', 'role' => 'assistant']), 422, 'VALIDATION_ERROR');
    }

    public function test_categories_are_the_five_ordered_by_name(): void
    {
        $this->send($this->assistantToken(), 'GET', '/categories')
            ->assertOk()
            ->assertJsonCount(5)
            ->assertJsonPath('0.name', 'Accesorios')
            ->assertJsonPath('4.name', 'Tabletas');
    }

    public function test_laptops_are_created_searched_read_replaced_and_retired(): void
    {
        $admin = $this->adminToken();

        $created = $this->send($admin, 'POST', '/laptops', [
            'name' => 'ThinkPad E14 Gen 5', 'brand' => 'Lenovo', 'model_code' => 'TP-E14-G5',
            'category_id' => self::PROGRAMMING, 'total_units' => 4,
        ])->assertCreated();
        $this->assertSame(
            ['id', 'name', 'brand', 'model_code', 'category', 'total_units', 'available_units', 'photo_key'],
            array_keys($created->json()),
        );
        $created->assertJsonPath('available_units', 4)->assertJsonPath('category.name', 'Programación')->assertJsonPath('photo_key', null);
        $id = $created->json('id');

        $this->assertErrorBody($this->send($admin, 'POST', '/laptops', [
            'name' => 'Otro', 'brand' => 'Lenovo', 'model_code' => 'TP-E14-G5', 'category_id' => self::PROGRAMMING, 'total_units' => 1,
        ]), 409, 'MODEL_CODE_TAKEN');
        $this->assertErrorBody($this->send($admin, 'POST', '/laptops', ['name' => 'Sin total']), 422, 'VALIDATION_ERROR');

        $this->send($admin, 'GET', '/laptops?q=thinkpad&size=10')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('size', 10)
            ->assertJsonPath('items.0.id', $id);
        $this->assertErrorBody($this->send($admin, 'GET', '/laptops?size=51'), 422, 'VALIDATION_ERROR');

        $this->send($admin, 'GET', "/laptops/{$id}")->assertOk()->assertJsonPath('model_code', 'TP-E14-G5');

        $this->send($admin, 'PUT', "/laptops/{$id}", [
            'name' => 'ThinkPad E14 Gen 6', 'brand' => 'Lenovo', 'category_id' => self::DESIGN, 'total_units' => 6,
        ])->assertOk()->assertJsonPath('total_units', 6)->assertJsonPath('available_units', 6)->assertJsonPath('category.name', 'Diseño');

        $this->send($admin, 'DELETE', "/laptops/{$id}")->assertNoContent();
        $this->assertErrorBody($this->send($admin, 'GET', "/laptops/{$id}"), 404, 'NOT_FOUND');
        $this->assertErrorBody($this->send($admin, 'GET', '/laptops/no-es-un-uuid'), 404, 'NOT_FOUND');
    }

    public function test_a_laptop_with_units_on_loan_cannot_be_retired(): void
    {
        $admin = $this->adminToken();
        $laptop = $this->createLaptop($admin, 'LAT-3440');
        $this->lend($admin, $this->createStudent($admin, '1001234567'), $laptop)->assertCreated();

        $this->assertErrorBody($this->send($admin, 'DELETE', "/laptops/{$laptop}"), 409, 'LAPTOP_HAS_OPEN_LOANS');
    }

    public function test_photo_is_uploaded_read_and_removed(): void
    {
        config(['laptops.photos_dir' => sys_get_temp_dir().'/contract-photos-'.bin2hex(random_bytes(4))]);
        $admin = $this->adminToken();
        $laptop = $this->createLaptop($admin, 'MBA-M2-13');
        $png = UploadedFile::fake()->createWithContent('foto.png', ImageBytes::png(2048));

        $uploaded = $this->call('PUT', "/api/v1/laptops/{$laptop}/photo", [], [], ['file' => $png], [
            'HTTP_AUTHORIZATION' => "Bearer {$admin}", 'HTTP_ACCEPT' => 'application/json',
        ])->assertOk();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $uploaded->json('photo_key'));

        $photo = $this->call('GET', "/api/v1/laptops/{$laptop}/photo", [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$admin}"])->assertOk();
        $this->assertSame('image/png', $photo->headers->get('Content-Type'));
        $this->assertSame(file_get_contents($png->getRealPath()), $photo->getContent());

        $this->send($admin, 'DELETE', "/laptops/{$laptop}/photo")->assertNoContent();
        $this->assertErrorBody($this->send($admin, 'GET', "/laptops/{$laptop}/photo"), 404, 'NOT_FOUND');

        $notAnImage = UploadedFile::fake()->createWithContent('foto.png', 'plain text, not an image');
        $rejected = $this->call('PUT', "/api/v1/laptops/{$laptop}/photo", [], [], ['file' => $notAnImage], [
            'HTTP_AUTHORIZATION' => "Bearer {$admin}", 'HTTP_ACCEPT' => 'application/json',
        ]);
        $this->assertErrorBody($rejected, 422, 'VALIDATION_ERROR');
        $this->assertErrorBody($this->send($admin, 'PUT', "/laptops/{$laptop}/photo"), 422, 'VALIDATION_ERROR');
    }

    public function test_students_are_created_listed_and_read_with_active_loans(): void
    {
        $assistant = $this->assistantToken();

        $created = $this->send($assistant, 'POST', '/students', ['full_name' => 'Laura Gómez', 'document_number' => '1001234567'])
            ->assertCreated();
        $id = $created->json('id');
        $this->assertSame(['id', 'full_name', 'document_number'], array_keys($created->json()));

        $this->assertErrorBody($this->send($assistant, 'POST', '/students', ['full_name' => 'Otra', 'document_number' => '1001234567']), 409, 'DOCUMENT_TAKEN');

        $this->send($assistant, 'GET', '/students?q=laura')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.id', $id);
        $this->send($assistant, 'GET', "/students/{$id}")->assertOk()->assertExactJson([
            'id' => $id, 'full_name' => 'Laura Gómez', 'document_number' => '1001234567', 'active_loans' => 0,
        ]);
        $this->assertErrorBody($this->send($assistant, 'GET', '/students/9a1e6c64-7c6f-4d0f-9a43-2f1a0f6d1e01'), 404, 'NOT_FOUND');
    }

    public function test_a_loan_is_lent_listed_read_and_returned(): void
    {
        $assistant = $this->assistantToken();
        $admin = $this->adminToken();
        $laptop = $this->createLaptop($admin, 'TP-E14-G5', 2);
        $student = $this->createStudent($assistant, '1001234567');

        $loan = $this->lend($assistant, $student, $laptop)
            ->assertCreated()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('loaned_at', '2026-11-16T14:15:00Z')
            ->assertJsonPath('items.0.due_at', '2026-12-01T14:15:00Z')
            ->assertJsonPath('items.0.returned_at', null)
            ->assertJsonPath('items.0.laptop_name', 'ThinkPad E14 Gen 5')
            ->assertJsonPath('items.0.category_name', 'Programación');
        $loanId = $loan->json('id');
        $itemId = $loan->json('items.0.id');

        $this->send($assistant, 'GET', "/laptops/{$laptop}")->assertJsonPath('available_units', 1);
        $this->send($assistant, 'GET', "/students/{$student}")->assertJsonPath('active_loans', 1);
        $this->send($assistant, 'GET', "/loans?student_id={$student}&status=open&from=2026-11-16&to=2026-11-16")
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.id', $loanId);
        $this->send($assistant, 'GET', "/loans/{$loanId}")->assertOk()->assertJsonPath('items.0.id', $itemId);

        $this->clock->advance('+2 days');
        $assistant = $this->tokenFor('camila', self::ASSISTANT_PASSWORD);
        $this->send($assistant, 'POST', "/loans/{$loanId}/items/{$itemId}/return")
            ->assertOk()
            ->assertJsonPath('id', $itemId)
            ->assertJsonPath('returned_at', '2026-11-18T14:15:00Z');
        $this->assertErrorBody($this->send($assistant, 'POST', "/loans/{$loanId}/items/{$itemId}/return"), 409, 'ITEM_ALREADY_RETURNED');

        $this->send($assistant, 'GET', "/loans/{$loanId}")->assertJsonPath('status', 'closed');
        $this->send($assistant, 'GET', "/laptops/{$laptop}")->assertJsonPath('available_units', 2);
        $this->assertErrorBody($this->send($assistant, 'GET', '/loans?status=perdido'), 422, 'VALIDATION_ERROR');
    }

    public function test_lending_rejects_in_the_order_of_the_contract(): void
    {
        $admin = $this->adminToken();
        $laptop = $this->createLaptop($admin, 'TP-E14-G5', 3);
        $other = $this->createLaptop($admin, 'LAT-3440', 3, 'Latitude 3440');
        $student = $this->createStudent($admin, '1001234567');
        $missing = '9a1e6c64-7c6f-4d0f-9a43-2f1a0f6d1e01';

        $this->assertErrorBody($this->send($admin, 'POST', '/loans', ['student_id' => $student, 'items' => []]), 422, 'VALIDATION_ERROR');
        $this->assertErrorBody($this->send($admin, 'POST', '/loans', ['student_id' => $student, 'items' => [['id' => $laptop]]]), 422, 'VALIDATION_ERROR');
        $this->assertErrorBody($this->lend($admin, $student, $laptop, $laptop), 422, 'DUPLICATE_LAPTOP_IN_LOAN');
        $this->assertErrorBody($this->lend($admin, $missing, $laptop), 404, 'NOT_FOUND');
        $this->assertErrorBody($this->lend($admin, $student, $missing), 404, 'NOT_FOUND');

        $limit = $this->lend($admin, $student, $laptop, $other);
        $this->assertErrorBody($limit, 422, 'STUDENT_LIMIT_EXCEEDED');
        $limit->assertJsonPath('details.0.issue', 'sin devolver: 0');

        $this->lend($admin, $student, $laptop)->assertCreated();
        $this->assertErrorBody($this->lend($admin, $student, $other), 422, 'STUDENT_LIMIT_EXCEEDED');

        $this->clock->advance('+16 days');
        $this->assertErrorBody($this->lend($this->adminToken(), $student, $other), 409, 'STUDENT_HAS_OVERDUE');
    }

    public function test_last_unit_lent_twice_answers_201_then_409(): void
    {
        $admin = $this->adminToken();
        $laptop = $this->createLaptop($admin, 'XPS-13-9340', 1, 'XPS 13');
        $first = $this->createStudent($admin, '1001234567', 'Laura Gómez');
        $second = $this->createStudent($admin, '1009876543', 'Andrés Rojas');

        $this->lend($admin, $first, $laptop)->assertCreated();
        $rejected = $this->lend($admin, $second, $laptop);

        $this->assertErrorBody($rejected, 409, 'LAPTOP_NOT_AVAILABLE');
        $rejected->assertJsonPath('details.0.field', 'items[0].laptop_id');
        $this->send($admin, 'GET', "/laptops/{$laptop}")->assertJsonPath('available_units', 0);
    }

    public function test_overdue_lines_and_most_requested_report(): void
    {
        $admin = $this->adminToken();
        $laptop = $this->createLaptop($admin, 'TP-E14-G5');
        $student = $this->createStudent($admin, '1001234567', 'Laura Gómez');
        $loan = $this->lend($admin, $student, $laptop)->assertCreated();

        $this->clock->advance('+18 days');
        $admin = $this->adminToken();

        $this->send($admin, 'GET', '/loans/overdue')->assertOk()->assertExactJson(['items' => [[
            'loan_id' => $loan->json('id'),
            'item_id' => $loan->json('items.0.id'),
            'laptop_name' => 'ThinkPad E14 Gen 5',
            'student_name' => 'Laura Gómez',
            'due_at' => '2026-12-01T14:15:00Z',
            'days_late' => 3,
        ]]]);

        $report = $this->send($admin, 'GET', '/reports/most-requested?from=2026-11-01&to=2026-11-30')->assertOk()->assertExactJson([
            'from' => '2026-11-01',
            'to' => '2026-11-30',
            'rows' => [[
                'laptop_id' => $laptop, 'laptop_name' => 'ThinkPad E14 Gen 5', 'category_name' => 'Programación', 'times_requested' => 1,
            ]],
        ]);
        $this->assertStringNotContainsString('Laura', $report->getContent());

        $this->assertErrorBody($this->send($admin, 'GET', '/reports/most-requested?from=2026-11-30&to=2026-11-01'), 422, 'INVALID_DATE_RANGE');
        $this->assertErrorBody($this->send($admin, 'GET', '/reports/most-requested?from=2026-11-01'), 422, 'VALIDATION_ERROR');
    }

    public function test_framework_errors_also_use_the_single_body(): void
    {
        $this->assertErrorBody($this->getJson('/api/v1/no-existe'), 404, 'NOT_FOUND');
        $this->assertErrorBody($this->deleteJson('/api/v1/health'), 404, 'NOT_FOUND');

        $response = $this->call('POST', '/api/v1/auth/login', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{ not json');
        $this->assertErrorBody($response, 422, 'VALIDATION_ERROR');
        $this->assertStringNotContainsString('vendor/', $response->getContent());
    }
}
