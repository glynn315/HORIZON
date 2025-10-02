<template>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <div class="flex min-h-screen flex-col md:flex-row">
        <!-- Sidebar -->
        <aside class="flex w-full flex-col justify-between bg-[#EDEDED] p-4 text-black md:w-64">
            <div>
                <h2 class="mb-4 text-xl font-semibold">Laravel Panel</h2>
                <nav class="flex flex-col gap-2">
                    <button @click="showSection = 'laraveltopics'" class="w-full rounded bg-white p-2 text-left hover:bg-[#EDEDED]">Laaravel Topics</button>
                    <button @click="showSection = 'quizzes'" class="w-full rounded bg-white p-2 text-left hover:bg-[#EDEDED]">
                        Manage Quizzes
                    </button>
                    <button @click="showSection = 'badges'" class="w-full rounded bg-white p-2 text-left hover:bg-[#EDEDED]">Badge</button>
                    <button @click="showSection = 'sandpit'" class="w-full rounded bg-white p-2 text-left hover:bg-[#EDEDED]">Sandpit</button>
                    <button @click="showSection = 'skilltests'" class="w-full rounded bg-white p-2 text-left hover:bg-[#EDEDED]">
                        Manage Skill Tests
                    </button>
                </nav>
            </div>
            <button
                @click="logout"
                class="mt-4 w-full rounded bg-red-500 px-4 py-2 font-semibold text-white transition duration-200 hover:bg-red-600"
            >
                Logout
            </button>
        </aside>

        <main class="flex-1 p-4 md:p-8">
            <div v-if="showSection === 'laraveltopics'" class="rounded bg-white p-4">
                <h2 class="mb-4 text-2xl font-bold text-blue-700">Laravel Topic Management</h2>

                <div v-for="course in courses.filter((c) => c && c.id === 1)" :key="'laravel-' + course.id" class="mb-6 rounded">
                    <div class="mb-4 flex border-b">
                        <button
                            v-for="tab in ['uploaded' , 'create', 'archived']"
                            :key="tab"
                            @click="activeTab[course.id] = tab"
                            :class="[
                                'px-4 py-2 text-sm font-medium',
                                activeTab[course.id] === tab ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-500',
                            ]"
                        >
                            {{ tab === 'uploaded' ? 'Uploaded Topics' : tab === 'create' ? 'Create New Topics' : 'Archived Topics'}}
                        </button>
                    </div>
                    <div v-if="activeTab[course.id] === 'uploaded'" class="p-5">
                        <div v-if="errorMessages[course.id]" class="mb-2 text-red-500">{{ errorMessages[course.id] }}</div>
                        <ul>
                            <li v-for="topic in standaloneTopics[course.id] || []" :key="topic.id" class="mb-3 rounded border bg-gray-50 p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-lg font-semibold">{{ topic.title }}</h4>
                                        <p class="text-sm text-gray-600">{{ topic.module_name }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="editingTopicId = editingTopicId === topic.id ? null : topic.id" class="text-blue-600 flex items-center gap-1">
                                            <span v-if="editingTopicId === topic.id"><X></X></span>
                                            <PenBoxIcon size="25" v-else/> 
                                        </button>
                                        <button @click="archiveTopic(topic.id)" class="text-red-600">
                                            <Archive size="25" color="red"/>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="editingTopicId === topic.id" class="mt-4 space-y-4">
                                    <div class="flex flex-col">
                                        <label class="mb-1 text-sm font-medium">Title</label>
                                        <input v-model="topic.title" class="rounded border p-2" />
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="mb-1 text-sm font-medium">Module Name</label>
                                        <input v-model="topic.module_name" class="rounded border p-2" />
                                    </div>
                                    <label class="block font-medium">Difficulty</label>
                                    <select v-model="topic.difficulty" class="w-full rounded border p-2" required>
                                        <option disabled value="">-- Select Difficulty --</option>
                                        <option value="1">Beginner</option>
                                        <option value="2">Intermediate</option>
                                        <option value="3">Advanced</option>
                                    </select>
                                    <label class="block font-medium">Content</label>
                                    <QuillEditor v-model:content="topic.content" contentType="html" class="border bg-white" toolbar="full" />
                                    <div class="flex gap-2">
                                        <button
                                            @click="updateStandaloneTopic(topic)"
                                            class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                                        >
                                            Save
                                        </button>
                                        <button @click="editingTopicId = null" class="text-gray-600 underline">Cancel</button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div v-if="activeTab[course.id] === 'create'" class="p-5">
                        <form @submit.prevent="createStandaloneTopicForCourse(course.id)" class="mb-4 w-full space-y-4">
                            <div class="flex flex-row gap-3">
                                <div class="flex flex-col w-3/6">
                                    <label class="mb-1 text-sm font-medium">
                                    Topic Title <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                    v-model="newTopics[course.id].title"
                                    placeholder="Enter topic title"
                                    class="rounded-md border p-2"
                                    required
                                    />
                                </div>

                                <div class="flex flex-col w-2/6">
                                    <label class="mb-1 text-sm font-medium">
                                    Module Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                    v-model="newTopics[course.id].module_name"
                                    placeholder="Enter module name"
                                    class="rounded-md border p-2"
                                    required
                                    />
                                </div>

                                <div class="flex flex-col w-1/6">
                                    <label class="block font-medium">Difficulty</label>
                                    <select v-model="newTopics[course.id].difficulty" class="w-full rounded border p-2" required>
                                    <option disabled value="">-- Select Difficulty --</option>
                                    <option value="1">Beginner</option>
                                    <option value="2">Intermediate</option>
                                    <option value="3">Advanced</option>
                                    </select>
                                </div>
                            </div>

                            <label class="block font-medium">Content</label>
                            <QuillEditor
                            v-model:content="newTopics[course.id].content"
                            contentType="html"
                            class="rounded border bg-white"
                            toolbar="full"
                            />
                            <div class="flex flex-col mt-2">
                                <label class="block font-medium">Upload PDF (optional)</label>
                                <input type="file" accept="application/pdf" @change="handlePdfUpload($event, course.id)" />
                                <div v-if="newTopics[course.id].pdfPreview" class="mt-2">
                                    <iframe :src="newTopics[course.id].pdfPreview" width="100%" height="400px"></iframe>
                                </div>
                            </div>

                            <button type="submit" class="rounded-md bg-green-600 px-4 py-2 font-medium text-white hover:bg-green-700">
                                <span v-if="newTopics[course.id].loading">Adding...</span>
                                <span v-else>Add Topic</span>
                            </button>
                        </form>
                    </div>

                    <div v-if="activeTab[course.id] === 'archived'" class="p-5">
                        <div v-if="archivedTopics[course.id] && archivedTopics[course.id].length > 0">
                            <ul>
                                <li v-for="topic in archivedTopics[course.id]" :key="'archived-' + topic.id" 
                                    class="mb-3 rounded border bg-gray-100 p-3">
                                    <h4 class="text-lg font-semibold">{{ topic.title }}</h4>
                                    <p class="text-sm text-gray-600">{{ topic.module_name }}</p>
                                </li>
                            </ul>
                        </div>
                        <p v-else class="text-gray-500">No archived topics yet.</p>
                    </div>
                </div>
            </div>
            <div v-if="showSection === 'quizzes'" class="rounded bg-white p-4 shadow">
                <h2 class="mb-4 text-2xl font-bold">Quiz Management</h2>
                <div class="mb-6 flex gap-3">
                    <button
                        @click="quizTab = 'uploaded'"
                        :class="quizTab === 'uploaded' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-black'"
                        class="rounded px-4 py-2"
                    >
                        Uploaded Quizzes
                    </button>
                    <button
                        @click="quizTab = 'create'"
                        :class="quizTab === 'create' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-black'"
                        class="rounded px-4 py-2"
                    >
                        Create Quiz
                    </button>
                    <button
                        @click="quizTab = 'archive'"
                        :class="quizTab === 'archive' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-black'"
                        class="rounded px-4 py-2"
                    >
                        Archive Quizzes
                    </button>
                </div>
                <div v-if="quizTab === 'create'">
                    <form @submit.prevent="createQuiz" class="mb-6 space-y-4">
                        <!-- Topic -->
                        <div class="flex flec-row justify-between gap-2">
                            <div class="flex flex-col w-2/3">
                                <label class="mb-1 text-sm font-medium">Topic</label>
                                <select v-model="newQuiz.topic_id" class="rounded border p-2" required>
                                    <option disabled value="">-- Select Topic --</option>
                                    <option v-for="topic in allTopics" :key="topic.id" :value="topic.id">
                                        {{ topic.title }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex flex-col w-1/3">
                                <label class="mb-1 text-sm font-medium">Quiz Type</label>
                                <select v-model="newQuiz.questionType" class="rounded border p-2" @change="displayFields">
                                    <option value="">Select Type</option>
                                    <option value="Blank">Fill in the Blanks</option>
                                    <option value="Choices">Multiple Choice</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <label class="mb-1 text-sm font-medium">Title</label>
                            <input v-model="newQuiz.title" class="rounded border p-2" required />
                        </div>
                        <div class="flex flex-col">
                            <label class="mb-1 text-sm font-medium">Question</label>
                            <textarea v-model="newQuiz.description" class="rounded border p-2"></textarea>
                        </div>

                        <div class="flex w-full flex-row-reverse gap-3">
                            <div class="flex flex-col">
                                <label class="mb-1 text-sm font-medium">Points</label>
                                <input v-model="newQuiz.score" class="rounded border p-2" required />
                            </div>
                            <div class="flex flex-col w-full">
                                <label class="mb-1 text-sm font-medium">Answer</label>
                                <input type="text" v-model="newQuiz.answer" class="rounded border p-2">
                                <div v-if="fieldsVisible" class="mt-2 flex flex-col gap-y-2">
                                    <label>Options</label>
                                    <div v-for="(opt, index) in options" :key="index" class="flex items-center rounded-md border">
                                        <div class="w-10 bg-green-300 p-2 text-center">{{ String.fromCharCode(65 + index) }}.</div>
                                        <input v-model="options[index]" type="text" class="w-full rounded-r-md border-none" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" v-model="newQuiz.is_published" />
                            <label class="text-sm">Publish immediately</label>
                        </div>

                        <button type="submit" class="mt-4 rounded bg-blue-600 px-4 py-2 text-white">Add Quiz</button>
                    </form>

                    <!-- Pending Quiz List -->
                    <div v-if="pendingQuizzes.length" class="mt-6">
                        <h4 class="mb-2 text-lg font-bold">Pending Quizzes</h4>
                        <ul class="mb-4">
                            <li v-for="(quiz, index) in pendingQuizzes" :key="index" class="mb-2 rounded border bg-gray-100 p-3">
                                <strong>{{ quiz.title }}</strong> — {{ quiz.questionType }}
                            </li>
                        </ul>
                        <button @click="saveAllQuizzes" class="rounded bg-green-600 px-4 py-2 text-white">Save All Quizzes</button>
                    </div>
                </div>
                <div v-if="quizTab === 'uploaded'">
                    <h4 class="mb-4 text-lg font-bold">Uploaded Quizzes</h4>
                    <ul class="space-y-4">
                        <li
                            v-for="quiz in quizzes"
                            :key="quiz.id"
                            class="border-b pb-2 flex justify-between items-start"
                        >
                            <div v-if="editingQuizId !== quiz.id">
                            <h3 class="text-lg font-semibold">{{ quiz.title }}</h3>
                            <p class="text-sm text-gray-600">{{ quiz.description }}</p>
                            <p class="text-sm hidden text-gray-600">Answer: {{ quiz.answer }}</p>
                            <p class="text-sm hidden text-gray-600">Score: {{ quiz.score }}</p>
                            <p class="text-sm text-gray-600">Status: {{ quiz.quizStatus }}</p>
                            </div>

                            <!-- Editing Form -->
                            <div v-else class="space-y-2">
                            <input v-model="editQuizData.title" placeholder="Quiz Title" class="border rounded p-2 w-full" />
                            <textarea v-model="editQuizData.description" placeholder="Description" class="border rounded p-2 w-full"></textarea>
                            <input v-model="editQuizData.answer" placeholder="Answer" class="border rounded p-2 w-full" />
                            <input v-model="editQuizData.score" placeholder="Score" class="border rounded p-2 w-full" />
                            <select v-model="editQuizData.quizStatus" class="border rounded p-2 w-full">
                                <option value="ACTIVE">Active</option>
                                <option value="ARCHIVE">Archive</option>
                            </select>
                            </div>

                            <div class="space-x-2">
                            <button
                                v-if="editingQuizId !== quiz.id"
                                @click="startEditingQuiz(quiz)"
                                class="text-blue-600"
                            >
                                <PenBoxIcon></PenBoxIcon>
                            </button>
                            <button
                                v-if="editingQuizId === quiz.id"
                                @click="updateQuiz(quiz.id)"
                                class="text-green-600"
                            >
                                <RefreshCcw></RefreshCcw>
                            </button>
                            <button
                                v-if="editingQuizId === quiz.id"
                                @click="editingQuizId = null"
                                class="text-gray-600"
                            >
                                <X></X>
                            </button>
                            <button
                                @click="deleteQuiz(quiz.id)"
                                class="text-red-600"
                            >
                                <Archive></Archive>
                            </button>
                            </div>
                        </li>
                        </ul>

                </div>
                <div v-if="quizTab === 'archive'">
                    <h4 class="mb-4 text-lg font-bold">Archived Quizzes</h4>
                    <ul>
                        <li v-for="quiz in archivedQuizzes" :key="quiz.id" class="mb-2 rounded border bg-gray-50 p-3">
                            <h4 class="font-semibold">{{ quiz.title }}</h4>
                            <p class="text-sm text-gray-600">{{ quiz.description }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Badges CRUD -->
            <div v-if="showSection === 'badges'" class="rounded bg-white p-4 shadow">
                <h2 class="mb-4 text-2xl font-bold">Add Badge</h2>
                <form @submit.prevent="createBadge" class="space-y-4">
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Title</label>
                        <input v-model="newBadge.title" class="rounded border p-2" required />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Criteria</label>
                        <textarea v-model="newBadge.description" class="rounded border p-2" required></textarea>
                    </div>
                    <div>
                        <div class="flex flex-col">
                            <label class="mb-1 text-sm font-medium">Topic</label>
                            <select v-model="newBadge.topic_id" class="rounded border p-2" required>
                                <option disabled value="">-- Select Topic --</option>
                                <option v-for="topic in allTopics" :key="topic.id" :value="topic.id">{{ topic.title }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Image URL</label>
                        <input type="file" @change="handleImageUpload($event, 'new')" accept="image/*" class="rounded border p-2" required />
                    </div>
                    <button type="submit" class="rounded bg-green-600 px-4 py-2 text-white">Create Badge</button>
                </form>
                <div class="mt-6">
                    <h3 class="mb-2 text-xl font-semibold">All Badges</h3>
                    <ul>
                        <li v-for="badge in badges" :key="badge.id" class="mb-2 rounded border bg-gray-50 p-3">
                            <div class="flex flex-row items-center gap-3">
                                <img :src="badge.image" class="h-12 w-12" />
                                <div>
                                    <h4 class="font-semibold">{{ badge.title }}</h4>
                                    <p class="text-sm">{{ badge.description }}</p>
                                </div>
                            </div>
                            <div v-if="editingBadgeId !== badge.id">
                                <div class="mt-1 cursor-pointer text-sm text-blue-600 underline" @click="startEditingBadge(badge)">Edit</div>
                            </div>
                            <div v-else class="mt-5 space-y-2">
                                <input v-model="editBadge.title" class="w-full rounded border p-1" placeholder="Title" />
                                <textarea v-model="editBadge.description" class="w-full rounded border p-1" placeholder="Descriotion"></textarea>
                                <input type="file" @change="handleImageUpload($event, 'edit')" accept="image/*" class="w-full rounded border p-1" />
                                <select name="" v-model="editBadge.course" id="" class="w-full rounded-md">
                                    <option value="">Select Course</option>
                                    <option value="Vue Js">Vue JS</option>
                                    <option value="Laravel">Laravel</option>
                                </select>
                                <div class="flex gap-2">
                                    <button @click="updateBadge" class="rounded bg-blue-600 px-3 py-1 text-white">Save</button>
                                    <button @click="cancelEditingBadge" class="text-gray-600 underline">Cancel</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div v-if="showSection === 'reports'" class="rounded bg-white p-4 shadow">
                <h2 class="mb-4 text-2xl font-bold">Reports</h2>
                <div class="space-y-4">
                    <div v-for="type in reportTypes" :key="type" class="flex items-center justify-between rounded border p-4">
                        <span class="capitalize">{{ type.replace('-', ' ') }} Report</span>
                        <div class="space-x-2">
                            <button @click="fetchReport(type)" class="rounded bg-blue-600 px-4 py-1 text-white">View</button>
                            <button @click="downloadPdf(type)" class="rounded bg-green-600 px-4 py-1 text-white">Download PDF</button>
                        </div>
                    </div>

                    <div v-if="reportResults" class="mt-6">
                        <pre class="max-h-[600px] overflow-auto rounded bg-gray-100 p-4 text-sm">{{ JSON.stringify(reportResults, null, 2) }}</pre>
                    </div>
                </div>
            </div>
            <div v-if="modalVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="relative w-full max-w-3xl rounded bg-white p-6 shadow-lg">
                    <button @click="closeModal" class="absolute right-2 top-2 text-xl text-gray-500 hover:text-black">&times;</button>
                    <h3 class="mb-4 text-xl font-bold">{{ selectedReport.title }}</h3>
                    <div class="max-h-[600px] space-y-6 overflow-auto text-sm">
                        <div v-if="Array.isArray(selectedReport.data)">
                            <div v-for="(entry, index) in selectedReport.data" :key="index" class="space-y-4 border-b pb-4">
                                <div v-if="entry.user">
                                    <h4 class="text-base font-semibold">User: {{ entry.user }}</h4>
                                    <table v-if="entry.progress" class="w-full border-collapse border">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th class="border p-2">Course</th>
                                                <th class="border p-2">Topics Complete</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(p, i) in entry.progress" :key="i">
                                                <td class="border p-2">{{ p.course }}</td>
                                                <td class="border p-2">{{ p.progress ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <table v-else-if="entry.topics" class="w-full border-collapse border">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th class="border p-2">Title</th>
                                                <th class="border p-2">Pre Avg</th>
                                                <th class="border p-2">Post Avg</th>
                                                <th class="border p-2">Gain</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(topic, i) in entry.topics" :key="i">
                                                <td class="border p-2">{{ topic.title }}</td>
                                                <td class="border p-2">{{ topic.pre_avg }}</td>
                                                <td class="border p-2">{{ topic.post_avg }}</td>
                                                <td class="border p-2">{{ topic.gain }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else-if="entry.framework">
                                    <table class="w-full border-collapse border">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th v-for="(val, key) in entry" :key="key" class="border p-2">
                                                    {{ key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()) }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td v-for="(val, key) in entry" :key="key" class="border p-2">
                                                    {{ val }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else-if="entry.name && entry.email && entry.badges_earned_count !== undefined">
                                    <table class="w-full border-collapse border">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th class="border p-2">Name</th>
                                                <th class="border p-2">Badges Earned</th>
                                                <th class="border p-2">Total Attempt</th>
                                                <th class="border p-2">Average Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="border p-2">{{ entry.name }}</td>
                                                <td class="border p-2">{{ entry.badges_earned_count }}</td>
                                                <td class="border p-2">{{ entry.total_quizzes_taken }}</td>
                                                <td class="border p-2">{{ entry.average_score }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-600">No data available for this report.</p>
                    </div>
                </div>
            </div>
            <div v-if="showSection === 'sandpit'" class="rounded bg-white p-4 shadow">
                <Sandpit mode="laravel"></Sandpit>
            </div>
            <div v-if="showSection === 'skilltests'" class="rounded bg-white p-4 shadow">
                <h2 class="mb-4 text-2xl font-bold">Skill Test Management</h2>
                <form @submit.prevent="createSkillTest" class="mb-6 space-y-4">
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Title</label>
                        <input v-model="newSkillTest.title" class="rounded border p-2" required />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Description</label>
                        <textarea v-model="newSkillTest.description" class="rounded border p-2"></textarea>
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Language</label>
                        <select v-model="newSkillTest.language" class="rounded border p-2" required>
                            <option value="php">PHP</option>
                            <option value="javascript">JavaScript</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Topics</label>
                        <select v-model="newSkillTest.topic_id" class="rounded border p-2" required>
                            <option disabled value="">-- Select Topic --</option>
                            <option v-for="topic in allTopics" :key="topic.id" :value="topic.id">{{ topic.title }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Score</label>
                        <input v-model="newSkillTest.score" class="rounded border p-2" required />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Test Input (stdin)</label>
                        <input v-model="newSkillTest.test_input" class="rounded border p-2" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Expected Output</label>
                        <input v-model="newSkillTest.expected_output" class="rounded border p-2" required />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 text-sm font-medium">Code Snippets</label>
                        <input type="file" @change="handleSnippetFile" class="rounded border p-2" required />
                    </div>
                    <button type="submit" class="rounded bg-green-600 px-4 py-2 text-white">Create Test</button>
                </form>
                <div v-if="skillTests.length">
                    <h3 class="mb-3 text-xl font-semibold">Existing Skill Tests</h3>
                    <ul>
                        <li v-for="test in skillTests" :key="test.id" class="mb-2 flex justify-between rounded border p-3">
                            <div>
                                <h4 class="font-semibold">
                                    {{ test.title }} <span class="text-xs text-gray-500">({{ test.language }})</span>
                                </h4>
                                <p class="text-sm text-gray-600">{{ test.description }}</p>
                                <p class="text-xs text-gray-400">Input: {{ test.test_input }} | Output: {{ test.expected_output }}</p>
                            </div>
                            <button @click="deleteSkillTest(test.id)" class="text-red-600 hover:underline"><Trash2></Trash2></button>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import Sandpit from '../sandpitComponent.vue';
import { Archive, PenBoxIcon, RefreshCcw, X, Trash2 } from 'lucide-vue-next';

const leaderboard = ref([]);
const quizTab = ref('uploaded');
const lessonsByTopic = ref({});
const newLesson = ref({});
const lessonToggles = ref({});
const archivedTopics = ref({});
const users = ref([]);
const courses = ref([]);
const quizzes = ref([]);
const archivedQuizzes = ref([]);
const allTopics = ref([]);
const modalVisible = ref(false);
const selectedReport = ref({ title: '', data: {} });
const standaloneTopics = ref({});
const newTopics = ref({});
const expandedCourses = ref({});
const errorMessages = ref({});
const editingTopicId = ref(null);
const openSection = ref(null);
const showSection = ref('laraveltopics');
const lessonTab = ref('laravel');
const options = ref(['', '', '', '']);
const editingQuizId = ref(null);
const editQuizData = ref({
    title: "",
    description: "",
    answer: "",
    score: "",
    quizStatus: "ACTIVE",
});
const editingBadgeId = ref(null);
const editingLessonId = ref(null);
const editLesson = ref({});
const editBadge = ref({});
const pendingQuizzes = ref([]);
const showSandpit = ref(false);
const activeTab = ref({});
const isLaravelCourse = (course) =>
    String(course.name || '')
        .toLowerCase()
        .includes('laravel');
const newQuiz = ref({
    title: '',
    description: '',
    questionType: '',
    topic_id: '',
    is_published: false,
    answer: '',
    score: '',
    course_id: '',
});
const fetchArchivedTopics = async (courseId) => {
    try {
        const response = await axios.get(`/api/courses/2/topics/archived`);
        archivedTopics.value[courseId] = response.data;
    } catch (error) {
        console.error('Error fetching archived topics:', error);
    }
};
const restoreTopic = async (topicId, courseId) => {
    try {
        await axios.put(`/api/topics/${topicId}`, { topicStatus: 'ACTIVE' });
        fetchArchivedTopics(courseId);
    } catch (error) {
        console.error('Error restoring topic:', error);
    }
};
const filteredTopics = computed(() => {
    return allTopics.value.filter((topic) => {
        if (lessonTab.value === 'vue') return topic.course_id === 2;
        if (lessonTab.value === 'laravel') return topic.course_id === 1;
        return true;
    });
});
const toggleSection = (section) => {
    openSection.value = section;
};
const backToMenu = () => {
    openSection.value = null;
};
const fetchReport = async (type) => {
    try {
        const res = await axios.get(`/api/reports/${type}`);
        selectedReport.value = {
            title: `${type.replace('-', ' ')} Report`,
            data: res.data,
        };
        modalVisible.value = true;
    } catch (err) {
        selectedReport.value = {
            title: 'Error',
            data: { message: 'Failed to load report.' },
        };
        modalVisible.value = true;
    }
};
const skillTests = ref([]);
const newSkillTest = ref({
    title: '',
    description: '',
    language: 'php',
    test_input: '',
    expected_output: '',
    score: '',
    topic_id: '',
    codesnippet: '',
    course_id: '2',
});
const startEditingQuiz = (quiz) => {
    editingQuizId.value = quiz.id;
    editQuizData.value = {
        title: quiz.title ?? '',
        description: quiz.description ?? '',
        answer: quiz.answer ?? '',
        score: quiz.score ?? '',
        quizStatus: quiz.quizStatus ?? 'ACTIVE',
    };
};
const updateQuiz = async (quizId) => {
    try {
        const response = await fetch(`/api/quizzes/${quizId}`, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
        },
        body: JSON.stringify(editQuizData.value),
        });

        const result = await response.json();

        if (response.ok) {
        const index = quizzes.value.findIndex((q) => q.id === quizId);
        if (index !== -1) {
            quizzes.value[index] = {
            ...quizzes.value[index],
            ...editQuizData.value,
            };
        }

        editingQuizId.value = null;
        editQuizData.value = {
            title: "",
            description: "",
            answer: "",
            score: "",
            quizStatus: "ACTIVE",
        };

        alert("Quiz updated successfully!");
        } else {
        alert("Failed to update quiz: " + (result.message || JSON.stringify(result)));
        }
    } catch (error) {
        console.error("Error updating quiz:", error);
        alert("Error updating quiz. See console for details.");
    }
};


const fetchSkillTests = async () => {
    try {
        const res = await axios.get('/api/skill-tests/display/2');
        skillTests.value = res.data;
    } catch (err) {
        console.error(err);
    }
};

const handleSnippetFile = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        newSkillTest.value.codesnippet = reader.result.split(',')[1];
    };
    reader.readAsDataURL(file);
};

const createSkillTest = async () => {
    try {
        const res = await axios.post('/api/skill-tests', newSkillTest.value, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        });

        skillTests.value.push(res.data.data);
        newSkillTest.value = {
            title: '',
            description: '',
            language: 'php',
            test_input: '',
            expected_output: '',
            score: '',
            topic_id: '',
            codesnippet: '',
            course_id: '',
        };
    } catch (err) {
        console.error(err.response?.data || err);
    }
};

const deleteSkillTest = async (id) => {
    if (!confirm('Are you sure?')) return;
    try {
        await axios.delete(`/api/skill-tests/${id}`);
        skillTests.value = skillTests.value.filter((t) => t.id !== id);
    } catch (err) {
        console.error(err);
    }
};
const startEditingBadge = (badge) => {
    editingBadgeId.value = badge.id;
    editBadge.value = { ...badge };
};

const cancelEditingBadge = () => {
    editingBadgeId.value = null;
    editBadge.value = {};
};
const updateBadge = async () => {
    try {
        await axios.put(`/api/badges/${editingBadgeId.value}`, editBadge.value);
        await fetchBadges();
        cancelEditingBadge();
    } catch (e) {
        alert('Failed to update badge');
        console.error(e);
    }
};
const closeModal = () => {
    modalVisible.value = false;
    selectedReport.value = { title: '', data: {} };
};
const difficultyLabel = (val) => {
    if (val === 1) return 'Beginner';
    if (val === 2) return 'Intermediate';
    if (val === 3) return 'Advanced';
    return 'Unknown';
};

const reportTypes = ['course-completion', 'framework-scorecard', 'gamification', 'assessment', 'framework-comparison'];
const reportResults = ref(null);
const fieldsVisible = ref(false);
const newBadge = ref({ title: '', description: '', image: '', topic_id: '', course: '2' });
const badges = ref([]);

const downloadPdf = async (type) => {
    try {
        window.open(`/api/reports/export-pdf?type=${type}`, '_blank');
    } catch (err) {
        console.error('Failed to download PDF', err);
    }
};
const fetchCourses = async () => {
    const res = await axios.get('/api/courses');
    courses.value = res.data;

    for (const course of courses.value) {
        newTopics.value[course.id] = { title: '', content: '', module_name: '', difficulty: '', loading: false };
        expandedCourses.value[course.id] = false;
        activeTab.value[course.id] = 'uploaded';

        const topicUrl = course.name === 'Laravel Frameworks' ? `/api/courses/${course.id}/laravel-topics` : `/api/courses/${course.id}/topics`;

        const topicRes = await axios.get(topicUrl);
        standaloneTopics.value[course.id] = topicRes.data;
    }
};

const fetchUsers = async () => {
    const res = await axios.get('/api/users');
    users.value = res.data;
};

const displayFields = async () => {
    const selected = newQuiz.value.questionType;

    if (selected === 'Choices') {
        fieldsVisible.value = true;
    } else {
        fieldsVisible.value = false;
    }
};

const fetchQuizzes = async () => {
    const res = await axios.get('/api/quizzes/fetchpercourse/2');
    quizzes.value = res.data;
};

const fetchQuizzesArchives = async () => {
    const res = await axios.get('/api/quizzes/fetchperCoursearchives/2');
    archivedQuizzes.value = res.data;
};

const fetchAllTopics = async () => {
    const res = await axios.get('/api/topics/fetchpercourse/2');
    allTopics.value = res.data;
};
const handlePdfUpload = (event, courseId) => {
    const file = event.target.files[0];
    if (file && file.type === 'application/pdf') {
        newTopics.value[courseId].pdfFile = file;
        newTopics.value[courseId].pdfPreview = URL.createObjectURL(file);
    } else {
        newTopics.value[courseId].pdfFile = null;
        newTopics.value[courseId].pdfPreview = null;
    }
};
const createStandaloneTopicForCourse = async (courseId) => {
    const course = courses.value.find((c) => c.id === courseId);
    const isLaravel = course.name === 'Laravel Frameworks';
    errorMessages.value[courseId] = '';
    newTopics.value[courseId].loading = true;

    try {
        const postUrl = isLaravel
            ? `/api/courses/${courseId}/laravel-topics`
            : `/api/courses/${courseId}/topics`;

        let payload;
        if (newTopics.value[courseId].pdfFile) {
            payload = new FormData();
            payload.append('title', newTopics.value[courseId].title);
            payload.append('module_name', newTopics.value[courseId].module_name || '');
            payload.append('difficulty', newTopics.value[courseId].difficulty || '');
            payload.append('content', newTopics.value[courseId].content || '');
            payload.append('pdf_path', newTopics.value[courseId].pdfFile);
        } else {
            payload = {
                title: newTopics.value[courseId].title,
                module_name: newTopics.value[courseId].module_name,
                difficulty: newTopics.value[courseId].difficulty,
                content: newTopics.value[courseId].content,
            };
        }

        const config = newTopics.value[courseId].pdfFile
            ? { headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }
            : { headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } };

        const res = await axios.post(postUrl, payload, config);

        const response = await axios.post(postUrl, payload, config);
        if (!standaloneTopics.value[courseId]) standaloneTopics.value[courseId] = [];
        standaloneTopics.value[courseId].push(response.data);
        newTopics.value[courseId] = { title: '', module_name: '', difficulty: '', content: '', pdfUrl: null, pdfUrl: '', loading: false };

        alert('Topic added successfully!');
    } catch (error) {
        console.error(error);
        errorMessages.value[courseId] = error.response?.data?.message || 'Failed to add topic';
    } finally {
        newTopics.value[courseId].loading = false;
    }
};



const updateStandaloneTopic = async (topic) => {
    const course = courses.value.find((c) => c.id === topic.course_id);
    const isLaravel = course.name === 'Laravel Frameworks';
    const updateUrl = isLaravel ? `/api/laravel-topics/${topic.id}` : `/api/topics/${topic.id}`;
    await axios.put(updateUrl, topic);
    editingTopicId.value = null;
};
const createQuiz = () => {
        pendingQuizzes.value.push({
            ...newQuiz.value,
            questionCategory: 'quiz',
            options: newQuiz.value.questionType === 'Choices' ? [...options.value] : [],
            quizStatus: 'ACTIVE',
            course_id: 2,
        });

    newQuiz.value.title = '';
    newQuiz.value.description = '';
    newQuiz.value.answer = '';
    newQuiz.value.score = '';
    if (newQuiz.value.questionType === 'Choices') {
    options.value = ['', '', '', ''];
    }
};
const handleImageUpload = async (event, type = 'new') => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        if (type === 'new') {
            newBadge.value.image = reader.result;
        } else if (type === 'edit') {
            editBadge.value.image = reader.result;
        }
    };
    reader.readAsDataURL(file);
};
const archiveTopic = async (topicId) => {
    try {
        await axios.put(`/api/topics/${topicId}/archive`);
        // Refresh topics list or filter archived ones
        standaloneTopics[course.id] = standaloneTopics[course.id].filter(t => t.id !== topicId);
    } catch (error) {
        console.error('Error archiving topic:', error);
    }
};
const saveAllQuizzes = async () => {
    try {
        for (const quiz of pendingQuizzes.value) {
            const { options: quizOptions, ...quizData } = quiz;
            const res = await axios.post('/api/quizzes', quizData);
            const savedQuiz = res.data;

            if (quiz.questionType === 'Choices' && quizOptions?.length) {
                await axios.post('/api/options/storeOptions', {
                    question_id: savedQuiz.id,
                    options: quizOptions.map((text) => ({ option_text: text })),
                });
            }

            quizzes.value.push(savedQuiz);
        }

        pendingQuizzes.value = [];
        alert('All quizzes saved successfully!');
    } catch (err) {
        console.error(err);
        alert('Failed to save some quizzes.');
    }
};

const deleteQuiz = async (id) => {
    await axios.delete(`/api/quizzes/${id}`);
    quizzes.value = quizzes.value.filter((q) => q.id !== id);
};

watch(showSection, async (val) => {
    if (val === 'users') await fetchUsers();
    if (val === 'quizzes') {
        await fetchQuizzes();
        await fetchQuizzesArchives();
        await fetchAllTopics();
    }
    if (val === 'laraveltopics') {
        for (const course of courses.value) {
            await fetchArchivedTopics(2);
        }
    }
});

const toggleCourse = (courseId) => {
    expandedCourses.value[courseId] = !expandedCourses.value[courseId];
};
const fetchBadges = async () => {
    const res = await axios.get('/api/badges/display/2');
    badges.value = res.data;
};

const createBadge = async () => {
    await axios.post('/api/badges', newBadge.value);
    await fetchBadges();
    newBadge.value = { title: '', description: '', image: '', topic_id: '', course: '2' };
};

const logout = async () => {
    await axios.post('/logout');
    window.location.href = '/';
};

onMounted(async () => {
    const lbRes = await fetch('/api/leaderboard');
    leaderboard.value = await lbRes.json();
    fetchBadges();
    fetchCourses();
    fetchAllTopics();
    fetchSkillTests();
    fetchArchivedTopics(2);
});
</script>
