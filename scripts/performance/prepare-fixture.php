<?php

declare(strict_types=1);
use App\Actions\ProcessArtworkImageAction;
use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;

$app = require __DIR__.'/fixture-bootstrap.php';
$database = config('database.connections.sqlite.database');
if ($database !== dirname(__DIR__, 2).'/storage/framework/testing/performance/fixture.sqlite') {
    throw new RuntimeException('Only the dedicated synthetic fixture may be reset.');
}
touch($database);
Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);
$user = User::factory()->create(['display_name' => 'Synthetic Admin', 'email' => 'admin@example.invalid', 'password_hash' => 'audit-pass', 'role' => UserRole::Admin]);
$category = Category::factory()->create(['name' => 'Audit', 'slug' => 'audit']);
$other = Category::factory()->create(['name' => 'Other', 'slug' => 'other']);
$imagePath = storage_path('framework/testing/performance/images/original.jpg');
mt_srand(42);
$image = imagecreatetruecolor(2400, 1600);
for ($y = 0; $y < 1600; $y += 8) {
    for ($x = 0; $x < 2400; $x += 8) {
        imagefilledrectangle($image, $x, $y, $x + 7, $y + 7, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
    }
}
imagejpeg($image, $imagePath, 90);
imagedestroy($image);
for ($id = 1; $id <= 45; $id++) {
    $artwork = Artwork::factory()->create(['id' => $id, 'category_id' => $id <= 30 ? $category->id : $other->id, 'title' => 'Synthetic artwork '.$id, 'image_url' => '/audit-images/original.jpg', 'created_at' => now()->startOfDay()]);
}
$artwork = Artwork::findOrFail(45);
$uploadPath = storage_path('framework/testing/performance/upload.jpg');
copy($imagePath, $uploadPath);
$media = (new ProcessArtworkImageAction)->execute($artwork, new UploadedFile($uploadPath, 'painting.jpg', 'image/jpeg', null, true));
Comment::factory()->count(50)->for($artwork)->for($user)->sequence(fn ($sequence) => ['body' => 'Synthetic comment '.$sequence->index])->create();
$sizes = ['original' => filesize($media->getPath())];
foreach (['intro', 'card', 'display'] as $conversion) {
    $sizes[$conversion] = ['bytes' => filesize($media->getPath($conversion)), 'dimensions' => array_slice(getimagesize($media->getPath($conversion)), 0, 2)];
}
file_put_contents(base_path('docs/performance/results/image-sizes.json'), json_encode(['fixture' => 'Deterministic 2400x1600 JPEG, seed 42, 8x8 random-color tiles, quality 90; synthetic, not a real artwork.', 'sizes' => $sizes], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Prepared isolated browser fixture.\n";
