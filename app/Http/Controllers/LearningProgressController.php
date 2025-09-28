<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
class LearningProgressController extends Controller
{
    
    
    public function getTopicsFromAttempts($userId)
    {
        $attempts = QuizAttempt::with(['topic.course'])
            ->where('user_id', $userId)
            ->where('type', 'post')
            ->latest('created_at') // optional: get latest attempts
            ->get()
            ->unique('topic_id'); // ensures only one attempt per topic

        $topics = $attempts->map(function ($attempt) {
            $topic = $attempt->topic;
            $course = $topic?->course;

            return [
                'topic_id' => $topic->id ?? null,
                'title' => $topic->title ?? 'Unknown',
                'score' => $attempt->score,
                'passed' => $attempt->passed,
                'course_id' => $course->id ?? null,
                'course_name' => $course->name ?? 'Unknown',
            ];
        })->filter(); // remove null topics

        return response()->json($topics);
    }

    public function getUserProgress($userId)
{
    $user = User::findOrFail($userId);
    $courses = Course::with('topics')->get();

    // Fetch quiz attempts for the user
    $rawAttempts = QuizAttempt::where('user_id', $userId)
        ->where('type', 'post')
        ->with('topic:id,title,course_id')
        ->orderByDesc('attempted_at')
        ->get();

    // Group by topic to aggregate scores & times
    $groupedByTopic = $rawAttempts->groupBy('topic_id');

    $topicStats = $groupedByTopic->map(function ($attempts) {
        $unique = $attempts->unique('quiz_id'); // prevent double-counting
        $topic = $attempts->first()->topic;

        return [
            'topic_id'   => $topic->id,
            'title'      => $topic->title,
            'score'      => $unique->sum('score'),
            'time_taken' => $unique->sum('time_taken'),
            'course_id'  => $topic->course_id,
        ];
    });

    // Build course-level progress
    $progress_by_course = $courses->map(function ($course) use ($topicStats) {
        $topics = $course->topics->where('topicStatus', '!=', 'ARCHIVED');

        // Topics that have at least one recorded attempt
        $completed = $topics->filter(fn($topic) => $topicStats->has($topic->id));

        return [
            'course_id'         => $course->id,
            'course_name'       => $course->name,
            'topics_total'      => $topics->count(),
            'topics_completed'  => $completed->count(),
            'overall_progress'  => $topics->count() > 0
                ? round(($completed->count() / $topics->count()) * 100)
                : 0,
            'topics' => $completed->map(function ($topic) use ($topicStats) {
                $data = $topicStats[$topic->id];
                return [
                    'topic_id'   => $data['topic_id'],
                    'title'      => $data['title'],
                    'score'      => $data['score'],
                    'time_taken' => $data['time_taken'],
                ];
            })->values()
        ];
    });

    return response()->json([
        'user_id'            => $user->id,
        'progress_by_course' => $progress_by_course
    ]);
}



}
