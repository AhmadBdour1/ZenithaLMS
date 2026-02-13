<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
                'permissions' => [
                    'system_management',
                    'manage_all_organizations',
                    'database_management',
                    'security_settings',
                    'backup_restore',
                    'api_management',
                    'view_all_revenue',
                    'manage_subscriptions',
                    'billing_management',
                    'refund_management',
                    'configure_ai_models',
                    'monitor_ai_usage',
                    'ai_cost_management'
                ],
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'organization_admin',
                'display_name' => 'Organization Admin',
                'description' => 'Manage organization settings and users',
                'permissions' => [
                    'manage_organization_profile',
                    'manage_branches',
                    'manage_departments',
                    'organization_settings',
                    'branding_customization',
                    'manage_all_users',
                    'assign_roles',
                    'manage_permissions',
                    'user_analytics',
                    'approve_courses',
                    'manage_categories',
                    'content_moderation',
                    'quality_control',
                    'view_organization_revenue',
                    'manage_subscriptions',
                    'pricing_control'
                ],
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'branch_manager',
                'display_name' => 'Branch Manager',
                'description' => 'Manage branch operations and local users',
                'permissions' => [
                    'manage_branch_users',
                    'branch_analytics',
                    'branch_schedule',
                    'local_settings',
                    'assign_instructors',
                    'manage_class_schedule',
                    'student_enrollment',
                    'progress_tracking'
                ],
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'instructor',
                'display_name' => 'Instructor',
                'description' => 'Create and manage courses, teach students',
                'permissions' => [
                    'create_courses',
                    'edit_own_courses',
                    'manage_lessons',
                    'create_assignments',
                    'grade_students',
                    'manage_quizzes',
                    'communicate_with_students',
                    'view_student_progress',
                    'provide_feedback',
                    'manage_discussions',
                    'use_ai_content_generator',
                    'ai_tutor_assistant',
                    'ai_grading_helper'
                ],
                'level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'teaching_assistant',
                'display_name' => 'Teaching Assistant',
                'description' => 'Assist instructors with course management',
                'permissions' => [
                    'help_with_grading',
                    'manage_discussions',
                    'answer_student_questions',
                    'upload_materials'
                ],
                'level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Access courses and learning materials',
                'permissions' => [
                    'access_purchased_courses',
                    'view_progress',
                    'submit_assignments',
                    'take_quizzes',
                    'download_certificates',
                    'communicate_with_instructors',
                    'join_discussions',
                    'study_groups',
                    'peer_interaction',
                    'ai_tutor_chat',
                    'personalized_recommendations',
                    'learning_assistant'
                ],
                'level' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'parent',
                'display_name' => 'Parent',
                'description' => 'Monitor child\'s learning progress',
                'permissions' => [
                    'view_child_progress',
                    'attendance_tracking',
                    'grade_reports',
                    'communication_with_school'
                ],
                'level' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
