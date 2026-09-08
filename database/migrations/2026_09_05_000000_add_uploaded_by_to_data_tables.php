<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The tables whose rows are uploaded by a user. */
    private const TABLES = ['poisoned', 'corruption', 'sppg', 'sppg_city'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                // Nullable: rows seeded before uploads were attributed keep counting,
                // they just have no one to credit.
                $blueprint->foreignId('uploaded_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }

        // Everything already in the database was uploaded by the admin account.
        $admin = User::query()->where('role', UserRole::Admin)->value('id');

        if ($admin) {
            foreach (self::TABLES as $table) {
                DB::table($table)->update(['uploaded_by' => $admin]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('uploaded_by');
            });
        }
    }
};
