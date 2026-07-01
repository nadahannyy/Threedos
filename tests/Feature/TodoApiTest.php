<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can create category.
     */
    public function test_can_create_category()
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Work',
            'description' => 'Work related tasks'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['id', 'name', 'description', 'created_at', 'updated_at']
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'Work',
                         'description' => 'Work related tasks'
                     ]
                 ]);

        $this->assertDatabaseHas('categories', ['name' => 'Work']);
    }

    /**
     * Test name is required for category.
     */
    public function test_cannot_create_category_without_name()
    {
        $response = $this->postJson('/api/categories', [
            'description' => 'No name category'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['success', 'message', 'errors'])
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation error.'
                 ]);
    }

    /**
     * Test duplicate category names are not allowed.
     */
    public function test_cannot_create_category_with_duplicate_name()
    {
        Category::create(['name' => 'Personal']);

        $response = $this->postJson('/api/categories', [
            'name' => 'Personal'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test listing categories.
     */
    public function test_can_list_categories()
    {
        Category::create(['name' => 'Home']);
        Category::create(['name' => 'Study']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJson([
                     'success' => true
                 ]);
    }

    /**
     * Test showing single category.
     */
    public function test_can_show_category()
    {
        $category = Category::create(['name' => 'Fitness', 'description' => 'Gym routines']);

        $response = $this->getJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $category->id,
                         'name' => 'Fitness',
                         'description' => 'Gym routines',
                         'tasks' => []
                     ]
                 ]);
    }

    /**
     * Test updating category.
     */
    public function test_can_update_category()
    {
        $category = Category::create(['name' => 'Old Name']);

        $response = $this->putJson('/api/categories/' . $category->id, [
            'name' => 'New Name'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $category->id,
                         'name' => 'New Name'
                     ]
                 ]);

        $this->assertDatabaseHas('categories', ['name' => 'New Name']);
    }

    /**
     * Test deleting category.
     */
    public function test_can_delete_category()
    {
        $category = Category::create(['name' => 'Temp']);

        $response = $this->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Category deleted successfully.'
                 ]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * Test can create task.
     */
    public function test_can_create_task()
    {
        $category = Category::create(['name' => 'Shopping']);

        $response = $this->postJson('/api/tasks', [
            'category_id' => $category->id,
            'title' => 'Buy Milk',
            'description' => 'Whole milk 2L',
            'due_date' => '2026-07-10 18:00:00'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['id', 'category_id', 'title', 'description', 'is_completed', 'due_date', 'created_at', 'updated_at', 'category']
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'category_id' => $category->id,
                         'title' => 'Buy Milk',
                         'description' => 'Whole milk 2L',
                         'is_completed' => false
                     ]
                 ]);

        $this->assertDatabaseHas('tasks', ['title' => 'Buy Milk']);
    }

    /**
     * Test creating task with invalid category fails.
     */
    public function test_cannot_create_task_with_invalid_category()
    {
        $response = $this->postJson('/api/tasks', [
            'category_id' => 999, // non-existent
            'title' => 'Invalid Task'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test listing tasks.
     */
    public function test_can_list_tasks()
    {
        $category = Category::create(['name' => 'Work']);
        Task::create(['category_id' => $category->id, 'title' => 'Task 1']);
        Task::create(['category_id' => $category->id, 'title' => 'Task 2']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJson([
                     'success' => true
                 ]);
    }

    /**
     * Test listing tasks by category.
     */
    public function test_can_list_tasks_by_category()
    {
        $cat1 = Category::create(['name' => 'Work']);
        $cat2 = Category::create(['name' => 'Home']);

        Task::create(['category_id' => $cat1->id, 'title' => 'Work Task']);
        Task::create(['category_id' => $cat2->id, 'title' => 'Home Task']);

        $response = $this->getJson('/api/categories/' . $cat1->id . '/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         ['title' => 'Work Task']
                     ]
                 ]);
    }

    /**
     * Test updating task.
     */
    public function test_can_update_task()
    {
        $category = Category::create(['name' => 'Work']);
        $task = Task::create(['category_id' => $category->id, 'title' => 'Old Title']);

        $response = $this->putJson('/api/tasks/' . $task->id, [
            'title' => 'New Title',
            'is_completed' => true
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $task->id,
                         'title' => 'New Title',
                         'is_completed' => true
                     ]
                 ]);

        $this->assertDatabaseHas('tasks', ['title' => 'New Title', 'is_completed' => true]);
    }

    /**
     * Test deleting task.
     */
    public function test_can_delete_task()
    {
        $category = Category::create(['name' => 'Work']);
        $task = Task::create(['category_id' => $category->id, 'title' => 'Temp Task']);

        $response = $this->deleteJson('/api/tasks/' . $task->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Task deleted successfully.'
                 ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /**
     * Test cascade delete on category deletes related tasks.
     */
    public function test_cascade_delete_tasks()
    {
        $category = Category::create(['name' => 'Project A']);
        $task = Task::create(['category_id' => $category->id, 'title' => 'Project Task']);

        // Assert they exist in DB
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);

        // Delete the category
        $response = $this->deleteJson('/api/categories/' . $category->id);
        $response->assertStatus(200);

        // Check if category is gone
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        // Check if task is also gone automatically due to cascade
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
