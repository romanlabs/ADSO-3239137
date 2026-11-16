<?php

namespace Tests\Feature;

use App\Application\Ports\PhotoStorage;
use App\Application\Services\CreateLaptopService;
use App\Application\Services\LaptopPhotoService;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CA-03.4 with the disk adapter and PostgreSQL: JPG or PNG up to 2 MB by content, the row keeps only an opaque key,
 * and a replaced or removed file is deleted from disk. The 422 status of a rejected file joins this test with T-16.
 */
final class LaptopPhotoTest extends TestCase
{
    use RefreshDatabase;

    private const TWO_MB = 2 * 1024 * 1024;

    private string $directory;

    private Laptop $laptop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/photos-'.bin2hex(random_bytes(4));
        config(['laptops.photos_dir' => $this->directory]);
        $this->laptop = $this->app->make(CreateLaptopService::class)
            ->create('MacBook Air M2', 'Apple', 'MBA-M2-13', '22222222-2222-4222-8222-222222222222', 3);
    }

    protected function tearDown(): void
    {
        foreach ($this->files() as $file) {
            unlink($this->directory.'/'.$file);
        }
        @rmdir($this->directory);

        parent::tearDown();
    }

    public function test_a_laptop_without_photo_stores_null_and_never_an_empty_string(): void
    {
        $this->assertTrue(DB::table('laptops.laptop')->where('id', $this->laptop->id)->whereNull('photo_key')->exists());
    }

    public function test_a_png_of_exactly_two_megabytes_is_accepted_and_stored_under_an_opaque_key(): void
    {
        $png = self::png(self::TWO_MB);

        $laptop = $this->photos()->attach($this->laptop->id, $png);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $laptop->photoKey());
        $this->assertSame($laptop->photoKey(), $this->storedKey());
        $this->assertSame([$laptop->photoKey()], $this->files());
        $this->assertSame(self::TWO_MB, filesize($this->directory.'/'.$laptop->photoKey()));

        $read = $this->photos()->read($this->laptop->id);
        $this->assertSame(['image/png', $png], [$read->mimeType, $read->bytes]);
    }

    public function test_a_three_megabyte_file_is_rejected_and_nothing_is_written(): void
    {
        $this->assertRejected(self::png(3 * 1024 * 1024));
    }

    public function test_a_file_that_is_not_jpg_or_png_by_content_is_rejected(): void
    {
        $this->assertRejected("GIF89a\x01\x00\x01\x00");
        $this->assertRejected('this is plain text pretending to be a .png');
    }

    public function test_replacing_the_photo_deletes_the_previous_file(): void
    {
        $first = $this->photos()->attach($this->laptop->id, self::png(1024))->photoKey();

        $second = $this->photos()->attach($this->laptop->id, self::jpeg())->photoKey();

        $this->assertNotSame($first, $second);
        $this->assertSame([$second], $this->files());
        $this->assertSame('image/jpeg', $this->photos()->read($this->laptop->id)->mimeType);
    }

    public function test_removing_the_photo_clears_the_key_first_and_then_deletes_the_file(): void
    {
        $this->photos()->attach($this->laptop->id, self::png(1024));

        $this->photos()->detach($this->laptop->id);

        $this->assertNull($this->storedKey());
        $this->assertSame([], $this->files());
    }

    public function test_a_key_that_looks_like_a_path_never_reaches_the_file_system(): void
    {
        $storage = $this->app->make(PhotoStorage::class);
        $key = $storage->put(self::png(1024), 'image/png');

        $this->assertNull($storage->get('../'.$key));
        $this->assertNull($storage->get('/etc/passwd'));
        $storage->delete('../../'.$key);

        $this->assertSame([$key], $this->files());
    }

    private function assertRejected(string $bytes): void
    {
        try {
            $this->photos()->attach($this->laptop->id, $bytes);
            $this->fail('The file must be rejected.');
        } catch (ValidationFailed $error) {
            $this->assertSame('VALIDATION_ERROR', $error->errorCode());
            $this->assertSame('file', $error->details()[0]['field']);
        }

        $this->assertNull($this->storedKey());
        $this->assertSame([], $this->files());
    }

    private function photos(): LaptopPhotoService
    {
        return $this->app->make(LaptopPhotoService::class);
    }

    private function storedKey(): ?string
    {
        return DB::table('laptops.laptop')->where('id', $this->laptop->id)->value('photo_key');
    }

    /** @return list<string> */
    private function files(): array
    {
        return is_dir($this->directory) ? array_values(array_diff(scandir($this->directory), ['.', '..'])) : [];
    }

    /** A valid 1x1 PNG padded with a private ancillary chunk up to the exact size asked for. */
    private static function png(int $totalBytes): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $head = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress("\x00\xff\x00\x00"));
        $tail = $chunk('IEND', '');

        return $head.$chunk('raNd', str_repeat("\0", $totalBytes - strlen($head) - strlen($tail) - 12)).$tail;
    }

    private static function jpeg(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00".str_repeat("\x00", 64)."\xFF\xD9";
    }
}
