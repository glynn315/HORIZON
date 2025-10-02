<?php

use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CompilerController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\LeaderboardsController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\SkillAttemptController;
use App\Http\Controllers\SkillTestController;
use App\Http\Controllers\UserDifficultyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\LaravelTopicController; 
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\UserProgressTracker;
use App\Http\Controllers\MyLearningController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\OptionController;
use Illuminate\Http\Request;
use App\Models\User;

// ✅ Health check
Route::middleware('api')->get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

// ✅ Courses
Route::apiResource('courses', CourseController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

// ✅ Vue Topics (uses shared `topics` table, expects `module_name`)
Route::get('/courses/{id}/topics', [TopicController::class, 'getTopicsByCourse']);
Route::post('/courses/{course_id}/topics', [TopicController::class, 'store']); // expects: title, content, module_name
Route::get('/topics', [TopicController::class, 'index']);
Route::get('/topics/fetchpercourse/{id}', [TopicController::class, 'fetchperCourse']);
Route::get('/topics/{topic}', [TopicController::class, 'show']);
Route::put('/topics/{topic}', [TopicController::class, 'update']); // expects: title, content, module_name
Route::delete('/topics/{topic}', [TopicController::class, 'destroy']);
Route::put('/topics/{topic}/archive', [TopicController::class, 'archive']);
Route::get('/courses/{id}/topics/archived', [TopicController::class, 'getArchivedTopics']);
Route::post('/topics/complete', [TopicController::class, 'finishTopic']);

// ✅ Laravel Topics (uses separate `laravel_topics` table, expects `module_name`)
Route::get('/courses/{course}/laravel-topics', [LaravelTopicController::class, 'index']);
Route::post('/courses/{course}/laravel-topics', [LaravelTopicController::class, 'store']); // expects: title, content, module_name
Route::put('/laravel-topics/{laravelTopic}', [LaravelTopicController::class, 'update']); // expects: title, content, module_name
Route::delete('/laravel-topics/{laravelTopic}', [LaravelTopicController::class, 'destroy']);

// ✅ Users (admin listing)
Route::get('/users', [UserController::class, 'index']);
Route::get('/admin', [UserController::class, 'admin']);

// ✅ User progress (protected)
Route::middleware(['auth:sanctum'])->get('/user-progress', [UserProgressTracker::class, 'userProgress']);

// ✅ MyLearning (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/mylearning/start/{courseId}', [MyLearningController::class, 'start']);
    Route::get('/mylearning/progress', [MyLearningController::class, 'progress']);
    Route::put('/mylearning/update-topic/{courseId}/{topicId}', [MyLearningController::class, 'updateTopic']);
});

// ✅ Quiz routes
Route::get('/quizzes', [QuizController::class, 'index']);
Route::post('/quizzes', [QuizController::class, 'store']);
Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);
Route::get('/displayQuiz', [QuizController::class, 'displayQuiz']);

Route::get('/pretest/{courseName}', [QuizController::class, 'getPretestByCourse']);

Route::get('/quizzes/fetchpercourse/{id}', [QuizController::class, 'fetchperCourse']);
Route::get('/quizzes/fetchperCoursearchives/{id}', [QuizController::class, 'fetchperCoursearchives']);

// Nested Questions under a Quiz
Route::get('/quizzes/{quiz}/questions', [QuestionController::class, 'index']);
Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store']);
Route::get('/questions/{question}', [QuestionController::class, 'show']);
Route::put('/questions/{question}', [QuestionController::class, 'update']);
Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);

// Nested Options under a Question
Route::get('/questions/{question}/options', [OptionController::class, 'index']);
Route::post('/questions/{question}/options', [OptionController::class, 'store']);
Route::get('/options/{option}', [OptionController::class, 'show']);
Route::put('/options/{option}', [OptionController::class, 'update']);
Route::post('/options/storeOptions', [OptionController::class, 'store']);
Route::delete('/options/{option}', [OptionController::class, 'destroy']);
Route::get('/displayPostQuiz/{id}', [QuestionController::class, 'displayPostQuiz']);
Route::get('/displayPreQuiz/{id}', [QuestionController::class, 'displayPreQuiz']);
Route::get('/options/by-question/{questionId}', [OptionController::class, 'getOptionsByQuestionId']);

Route::get('/user-attempted-topics/{userId}', [LearningProgressController::class, 'getTopicsFromAttempts']);
Route::get('/user-progress/{userId}', [LearningProgressController::class, 'getUserProgress']);

Route::get('/user-attempts/{userId}', [QuizAttemptController::class, 'getAttemptsByUser']);
Route::post('/recordAttempt', [QuizAttemptController::class, 'store']);

Route::get('/post-attempts/{id}', [LeaderboardsController::class, 'postAttempts']);
Route::get('/leaderboard', [LeaderboardsController::class, 'index']);
Route::get('/rating/{id}', [LeaderboardsController::class, 'displayRatings']);
Route::get('/leaderboard-counts', [LeaderboardsController::class, 'counts']);

Route::post('/upload-image', [ImageUploadController::class, 'store'])->name('upload.image');
Route::get('/user-topic-progress/{userId}/{courseId}', [QuizAttemptController::class, 'getCompletedTopics']);
Route::get('/badges/check/{id}', [BadgeController::class, 'check']);
Route::get('/badges/claimed/{id}', [BadgeController::class, 'claimed']);
Route::post('/badges/claim/{id}', [BadgeController::class, 'claim']);
Route::get('/badges/display/{id}', [BadgeController::class, 'fetchperCourse']);
Route::post('/badges', [BadgeController::class, 'store']);
Route::get('/badges', [BadgeController::class, 'index']);
Route::get('/topic-badges/{userId}', [BadgeController::class, 'getTopicBadges']);
Route::get('/validateBadge/{id}', [BadgeController::class, 'getBadges']);

//Working
Route::get('/reports/course-completion', [BadgeController::class, 'generateCourseCompletionReport']);

//Working
Route::get('/reports/framework-scorecard', [BadgeController::class, 'generateFrameworkProficiencyScorecard']);

//Working
Route::get('/reports/gamification', [BadgeController::class, 'generateGamificationReport']);
Route::put('/badges/{id}', [BadgeController::class, 'update']);
//Working
Route::get('/reports/assessment', [BadgeController::class, 'generateAssessmentReport']);

// Working
Route::get('/reports/framework-comparison', [BadgeController::class, 'generateFrameworkComparisonReport']);

// PDF Export Endpoint
Route::get('/reports/export-pdf', [BadgeController::class, 'exportReportToPdf']);

Route::post('/user-difficulty', [UserDifficultyController::class, 'store']);
Route::get('/user-difficulty/{user_id}/{course_name}', [UserDifficultyController::class, 'show']);
Route::get('/user-difficulty/{user_id}', [UserDifficultyController::class, 'index']);




Route::get('/skill-tests', [SkillTestController::class, 'index']);
Route::post('/skill-tests', [SkillTestController::class, 'store']);
Route::get('/skill-tests/{skillTest}', [SkillTestController::class, 'show']);
Route::put('/skill-tests/{skillTest}', [SkillTestController::class, 'update']);
Route::delete('/skill-tests/{skillTest}', [SkillTestController::class, 'destroy']);
Route::post('/skill-tests/run', [SkillTestController::class, 'run']);
Route::get('/skill-tests/by-topic/{topicId}', [SkillTestController::class, 'byTopic']);


Route::post('/skill-tests/{testId}/attempts', [SkillAttemptController::class, 'store']);
Route::get('/my-attempts', [SkillAttemptController::class, 'myAttempts']);
Route::get('/skill-tests/display/{id}', [SkillTestController::class, 'fetchperCourse']);
Route::apiResource('lessons', LessonController::class);
Route::get('/topics/{id}/lessons', [LessonController::class, 'byTopic']);
Route::put('/lessons/{id}', [LessonController::class, 'update']);
Route::post('/compile', [CompilerController::class, 'runCode']);
Route::post('/simulate-laravel', function (Request $request) {
    $code = $request->input('code');
    $template = $request->input('template', '');

    // Default output
    $output = 'Unknown Laravel code';

    // Detect return view(...)
    if (preg_match("/return\s+view\(['\"](\w+)['\"],\s*(\[.*\])\)/", $code, $matches)) {
        $varsCode = $matches[2];   // ['users' => [...]]
        $vars = eval("return $varsCode;");

        $rendered = $template;

        // ✅ Handle foreach loops
        $rendered = preg_replace_callback(
            '/@foreach\((\$\w+)\s+as\s+\$(\w+)\)(.*?)@endforeach/s',
            function ($m) use ($vars) {
                $collectionName = ltrim($m[1], '$'); // e.g. users
                $itemName = $m[2];                   // e.g. user
                $innerTemplate = $m[3];

                $result = '';
                foreach ($vars[$collectionName] as $item) {
                    $line = preg_replace_callback(
                        '/{{\s*' . $itemName . '\s*}}/',
                        fn($mm) => $item,
                        $innerTemplate
                    );
                    $result .= $line;
                }
                return $result;
            },
            $rendered
        );

        // ✅ Handle simple {{ var }}
        $rendered = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($m) use ($vars) {
            return $vars[$m[1]] ?? '';
        }, $rendered);

        $output = $rendered;
    }

    return response()->json([
        'output' => $output
    ]);
});