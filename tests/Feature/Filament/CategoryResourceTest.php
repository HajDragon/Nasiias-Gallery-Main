<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_categories_list(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'Expressionism']);

        // Act
        $response = $this->actingAs($admin)->get('/admin/categories');

        // Assert
        $response->assertOk();
        $response->assertSee('Expressionism');
    }

    public function test_admin_can_delete_category_via_table_action(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'DeleteMe']);

        // Act
        $this->actingAs($admin);
        Livewire::test(ListCategories::class)
            ->callTableAction('delete', $category);

        // Assert
        $this->assertModelMissing($category);
    }

    public function test_admin_can_delete_category_via_edit_page(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'ToDiscard']);

        // Act
        $this->actingAs($admin);
        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->callAction('delete');

        // Assert
        $this->assertModelMissing($category);
    }
}
