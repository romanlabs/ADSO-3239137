<?php

namespace Tests\Feature;

use App\Application\Ports\CategoryRepository;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LaptopRepository;
use App\Application\Ports\PageRequest;
use App\Application\Ports\StudentRepository;
use App\Application\Ports\UserRepository;
use App\Domain\Category;
use App\Domain\DocumentNumber;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\ModelCodeTaken;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\Laptop;
use App\Domain\ModelCode;
use App\Domain\Quantity;
use App\Domain\Role;
use App\Domain\Student;
use App\Domain\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T-12: the persistence adapters of laptop, student, user and category against the real PostgreSQL schema. */
final class RepositoriesTest extends TestCase
{
    use RefreshDatabase;

    private const PROGRAMMING = '33333333-3333-4333-8333-333333333333';

    private const DESIGN = '22222222-2222-4222-8222-222222222222';

    private LaptopRepository $laptops;

    private StudentRepository $students;

    private UserRepository $users;

    private CategoryRepository $categories;

    private IdGenerator $ids;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laptops = $this->app->make(LaptopRepository::class);
        $this->students = $this->app->make(StudentRepository::class);
        $this->users = $this->app->make(UserRepository::class);
        $this->categories = $this->app->make(CategoryRepository::class);
        $this->ids = $this->app->make(IdGenerator::class);
    }

    public function test_categories_are_the_five_seeded_ones_ordered_by_name(): void
    {
        $names = array_map(fn (Category $category) => $category->name, $this->categories->all());

        $this->assertSame(['Accesorios', 'Básicos', 'Diseño', 'Programación', 'Tabletas'], $names);
        $this->assertSame('Diseño', $this->categories->find(self::DESIGN)?->name);
        $this->assertNull($this->categories->find('not-a-uuid'));
    }

    public function test_a_laptop_is_stored_and_read_back_with_every_field(): void
    {
        $laptop = $this->laptop('ThinkPad E14 Gen 5', 'TP-E14-G5', 4);

        $read = $this->laptops->find($laptop->id);

        $this->assertNotNull($read);
        $this->assertSame(['ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', 'Programación', 4, 4, null, null], [
            $read->name(), $read->brand(), $read->modelCode->value, $read->category()->name,
            $read->totalUnits(), $read->availableUnits(), $read->photoKey(), $read->deletedAt(),
        ]);
    }

    public function test_unknown_or_malformed_ids_are_reported_as_missing(): void
    {
        $this->assertNull($this->laptops->find($this->ids->next()));
        $this->assertNull($this->laptops->find('42'));
        $this->assertSame([], $this->laptops->findMany(['42', $this->ids->next()]));
        $this->assertNull($this->students->find('../etc'));
    }

    public function test_a_retired_laptop_is_hidden_but_still_readable_for_returns(): void
    {
        $laptop = $this->laptop('Latitude 3440', 'LAT-3440', 2);
        $laptop->retire(new DateTimeImmutable('2026-11-16T10:00:00Z'));
        $this->laptops->save($laptop);

        $this->assertNull($this->laptops->find($laptop->id));
        $this->assertSame([], $this->laptops->findMany([$laptop->id]));
        $this->assertEquals(new DateTimeImmutable('2026-11-16T10:00:00Z'), $this->laptops->findIncludingRetired($laptop->id)?->deletedAt());
        $this->assertFalse($this->laptops->modelCodeInUse('LAT-3440'));
    }

    public function test_model_code_is_unique_among_active_laptops_only(): void
    {
        $first = $this->laptop('Latitude 3440', 'LAT-3440', 2);

        try {
            $this->laptop('Otro Latitude', 'LAT-3440', 1);
            $this->fail('Expected ModelCodeTaken.');
        } catch (ModelCodeTaken) {
            // The outer transaction must still be usable after the unique violation.
        }

        $first->retire(new DateTimeImmutable('2026-11-16T10:00:00Z'));
        $this->laptops->save($first);
        $this->laptop('Latitude 3440 nuevo', 'LAT-3440', 1);

        $this->assertSame(2, DB::table('laptops.laptop')->where('model_code', 'LAT-3440')->count());
    }

    public function test_find_many_returns_active_laptops_keyed_by_id(): void
    {
        $a = $this->laptop('MacBook Air M2', 'MBA-M2-13', 3);
        $b = $this->laptop('IdeaPad 3', 'IDP-3-15', 3);

        $found = $this->laptops->findMany([$b->id, $a->id, $this->ids->next()]);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], array_keys($found));
        $this->assertSame('IdeaPad 3', $found[$b->id]->name());
    }

    public function test_search_matches_name_or_brand_ignoring_case_and_orders_by_name_then_id(): void
    {
        $this->laptop('zBook Firefly', 'ZBK-FF-14', 1, 'HP');
        $this->laptop('ThinkPad E14', 'TP-E14-G5', 1);
        $this->laptop('IdeaPad 3', 'IDP-3-15', 1);
        $this->laptop('MacBook Air', 'MBA-M2-13', 1, 'Apple', self::DESIGN);

        $byBrand = $this->laptops->search('lenovo', null, PageRequest::of());
        $byCategory = $this->laptops->search(null, self::DESIGN, PageRequest::of());
        $secondPage = $this->laptops->search(null, null, PageRequest::of(2, 2));

        $this->assertSame(['IdeaPad 3', 'ThinkPad E14'], $this->names($byBrand->items));
        $this->assertSame(['MacBook Air'], $this->names($byCategory->items));
        $this->assertSame(['ThinkPad E14', 'zBook Firefly'], $this->names($secondPage->items));
        $this->assertSame(4, $secondPage->total);
        $this->assertSame(0, $this->laptops->search(null, 'bad-id', PageRequest::of())->total);
    }

    public function test_search_treats_like_wildcards_as_plain_text(): void
    {
        $this->laptop('Pantalla 100% sRGB', 'PNT-100-SRGB', 1);
        $this->laptop('Pantalla 1000 nits', 'PNT-1000-NT', 1);

        $this->assertSame(['Pantalla 100% sRGB'], $this->names($this->laptops->search('100%', null, PageRequest::of())->items));
        $this->assertSame(0, $this->laptops->search('_', null, PageRequest::of())->total);
    }

    public function test_save_writes_units_only_if_they_still_hold_the_value_that_was_read(): void
    {
        $laptop = $this->laptop('ThinkPad E14', 'TP-E14-G5', 1);
        $mine = $this->laptops->find($laptop->id);

        // Another request takes the last unit after this one read the laptop.
        DB::table('laptops.laptop')->where('id', $laptop->id)->update(['available_units' => 0]);

        $mine->update('ThinkPad E14 Gen 5', 'Lenovo', $mine->category(), Quantity::from(1));

        $this->expectException(LaptopNotAvailable::class);
        $this->laptops->save($mine);
    }

    public function test_save_succeeds_twice_in_a_row_because_the_witness_moves_with_each_write(): void
    {
        $laptop = $this->laptop('ThinkPad E14', 'TP-E14-G5', 2);
        $laptop->update('ThinkPad E14', 'Lenovo', $laptop->category(), Quantity::from(5));
        $this->laptops->save($laptop);
        $laptop->attachPhoto('a1b2c3');
        $this->laptops->save($laptop);

        $row = DB::table('laptops.laptop')->where('id', $laptop->id)->first();
        $this->assertSame([5, 5, 'a1b2c3'], [$row->total_units, $row->available_units, $row->photo_key]);
    }

    public function test_a_student_is_stored_once_per_document_and_found_by_name_or_document(): void
    {
        $camila = $this->student('Camila Rojas', '1001234567');
        $this->student('andrés Pérez', '1009876543');
        $this->student('Beatriz Gómez', 'CE778899');

        $this->assertEquals($camila, $this->students->find($camila->id));
        $this->assertTrue($this->students->documentExists('1001234567'));
        $this->assertSame(['andrés Pérez', 'Beatriz Gómez', 'Camila Rojas'], $this->fullNames($this->students->search(null, PageRequest::of())->items));
        $this->assertSame(['Beatriz Gómez'], $this->fullNames($this->students->search('ce77', PageRequest::of())->items));
        $this->assertSame(['Camila Rojas'], $this->fullNames($this->students->search('ROJAS', PageRequest::of())->items));

        $this->expectException(DocumentTaken::class);
        $this->student('Otra Persona', '1001234567');
    }

    public function test_a_user_is_found_by_normalized_username_and_the_name_is_unique(): void
    {
        $user = User::createAssistant($this->ids->next(), 'Camila ', 'not-a-real-hash', 'assistant');
        $this->users->add($user);

        $read = $this->users->findByUsername('camila');

        $this->assertSame([$user->id, 'camila', Role::Assistant], [$read?->id, $read?->username, $read?->role]);
        $this->assertTrue($this->users->usernameExists('camila'));
        $this->assertNull($this->users->findByUsername('nadie'));

        $this->expectException(UsernameTaken::class);
        $this->users->add(User::createAssistant($this->ids->next(), 'camila', 'not-a-real-hash', 'assistant'));
    }

    private function laptop(string $name, string $code, int $units, string $brand = 'Lenovo', string $categoryId = self::PROGRAMMING): Laptop
    {
        $category = $this->categories->find($categoryId);
        $laptop = Laptop::register($this->ids->next(), $name, $brand, ModelCode::from($code), $category, Quantity::from($units));
        $this->laptops->add($laptop);

        return $laptop;
    }

    private function student(string $name, string $document): Student
    {
        $student = Student::register($this->ids->next(), $name, DocumentNumber::from($document));
        $this->students->add($student);

        return $student;
    }

    /** @param list<Laptop> $laptops */
    private function names(array $laptops): array
    {
        return array_map(fn (Laptop $laptop) => $laptop->name(), $laptops);
    }

    /** @param list<Student> $students */
    private function fullNames(array $students): array
    {
        return array_map(fn (Student $student) => $student->fullName, $students);
    }
}
