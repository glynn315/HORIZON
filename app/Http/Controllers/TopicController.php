<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Topic;
use App\Models\Course;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TopicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Topic::all()->where('topicStatus' , 'ACTIVE');
    }

    public function fetchperCourse($id){
        $displayQuiz = Topic::where('course_id','=',$id)->where('topicStatus' , 'ACTIVE')->get();

        return response()->json($displayQuiz);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $course_id)
    {
        // Validate input
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'module_name' => 'required|string|max:255',
            'difficulty' => 'required|in:1,2,3',
            'content' => 'required|string',
            'pdf_path' => 'nullable|mimes:pdf|max:10240', // max 10MB
        ]);

        // Ensure the course exists
        $course = Course::findOrFail($course_id); 

        // Create new topic
        $topic = new Topic();
        $topic->title = $validated['title'];
        $topic->module_name = $validated['module_name'];
        $topic->course_id = $course_id;
        $topic->difficulty = $validated['difficulty'];
        $topic->content = $validated['content'];
        $topic->topicStatus = 'ACTIVE';

        if ($request->hasFile('pdf_path')) {
            $path = $request->file('pdf_path')->store('pdfs', 'public');
            $topic->pdf_path = $path;
        }

        $topic->save();
        return response()->json([
            'id' => $topic->id,
            'title' => $topic->title,
            'module_name' => $topic->module_name,
            'difficulty' => $topic->difficulty,
            'content' => $topic->content,
            'pdf_url' => $topic->pdf_path ? asset('' . $topic->pdf_path) : null,
            'topicStatus' => $topic->topicStatus,
        ], 201);
    }

    public function show(Topic $topic)
    {
        //
    }
    public function edit(Topic $topic)
    {
        return view('admin.topics.edit', compact('topic'));
    }
    public function update(Request $request, Topic $topic)
    {
        $request->validate([
        'title' => 'required|string|max:255',
        'module_name' => 'required|string|max:255',
        'difficulty' => 'required|in:1,2,3',
        'content' => 'required|string',
    ]);

    $topic->update([
        'title' => $request->title,
        'module_name' => $request->module_name,
        'difficulty' => $request->difficulty,
        'content' => $request->content,
    ]);

    return response()->json(['message' => 'Topic updated successfully.', 'topic' => $topic]);
    }

    public function destroy(Topic $topic)
    {
        Log::info('Deleting topic:', ['id' => $topic->id]);


        DB::table('user_courses')
            ->where('last_topic_id', $topic->id)
            ->update(['last_topic_id' => null]);

        $topic->delete();

        return response()->json(['message' => 'Topic deleted successfully.']);
    }
    public function archive(Topic $topic)
    {
        $topic->update([
            'topicStatus' => 'ARCHIVED',
        ]);

        return response()->json([
            'message' => 'Topic archived successfully.',
            'topic' => $topic
        ]);
    }

    public function getArchivedTopics($courseId)
    {
        $topics = Topic::where('course_id', $courseId)
                    ->where('topicStatus', 'ARCHIVED')
                    ->get();

        return response()->json($topics);
    }

    public function vueTopics()
    {   
        $topics = Topic::where('course_id', 1)->get();
        return view('admin.topics.vue', compact('topics'));
    }
    public function getTopicsByCourse($id)
    {
        $topics = Topic::where('course_id', $id)
            ->where('topicStatus', 'ACTIVE')
            ->get()
            ->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'module_name' => $topic->module_name,
                    'difficulty' => $topic->difficulty,
                    'content' => $topic->content,
                    'topicStatus' => $topic->topicStatus,
                    'pdf_url' => $topic->pdf_path 
                        ? asset('storage/' . $topic->pdf_path) 
                        : null,
                ];
            });

        return response()->json($topics);
    }


    public function completeTopic($userId, $topicId)
    {
        $badge = Badge::where('topic_id', $topicId)->first();

        if ($badge) {
            UserBadge::firstOrCreate(
                ['user_id' => $userId, 'badge_id' => $badge->id],
                ['earned_at' => now()]
            );
        }
    }

    public function finishTopic(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'topic_id' => 'required|exists:topics,id',
        ]);

        $userId = $request->user_id;
        $topicId = $request->topic_id;

        // 🔹 (Optional) You may want to save a record of topic completion in another table
        // Example: UserTopic::updateOrCreate(...)
        // If you don’t track completions, you can skip this.

        // 🔹 Check if this topic has a badge
        $badge = Badge::where('topic_id', $topicId)->first();

        if ($badge) {
            // Assign badge if not already earned
            $userBadge = UserBadge::firstOrCreate(
                ['user_id' => $userId, 'badge_id' => $badge->id],
                ['earned_at' => now()]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Topic marked complete and badge assigned!',
                'badge' => $badge,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Topic marked complete. No badge for this topic.',
        ]);
    }
    
}
