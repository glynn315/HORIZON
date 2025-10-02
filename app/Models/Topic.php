<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'course_id',
        'module_name',
        'difficulty',
        'content',
        'pdf_path',
        'topicStatus'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function completedTopics()
    {
    return $this->belongsToMany(\App\Models\Topic::class, 'topic_user')->withTimestamps();
    }

    public function quizzes()
    {
    return $this->hasMany(Quiz::class);
    }
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}
