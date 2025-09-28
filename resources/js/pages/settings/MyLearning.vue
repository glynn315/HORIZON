<template>
  <div class="flex h-screen w-full flex-wrap bg-zinc-800">
    <!-- Sidebar -->
    <aside class="flex h-screen w-60 flex-col bg-white">
      <h1 class="p-10 text-2xl font-normal text-zinc-800 w-full text-center !font-bold">{{ header }}</h1>
      <div class="flex flex-row items-center gap-3 px-3 py-0">
        <div class=" border h-16 w-16 rounded-full overflow-hidden">
          <img :src="user.photo" alt="" class="w-full h-full">
        </div>
        <p v-if="user" class="text-base font-normal text-zinc-800 p-2">
        {{ user.firstname }} {{ user.lastname }}
        </p>
      </div>

      <nav class="flex flex-col space-y-6 pl-10 pt-14">
        <a href="dashboard" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
          <LayoutDashboard />
          Dashboard
        </a>
        <a href="/test" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
          <BookCopyIcon />Course</a> 
        <a href="/mylearning" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
          <BookCheckIcon/>My Learning</a> 
        <a href="/sandpit" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
          <TerminalSquareIcon/>Sandpit</a> 
        <a href="/badges" class="text-base font-normal text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
          <BadgeCheckIcon/>Badge</a> 

        <a href="/settings" class="text-base font-medium text-zinc-800 hover:text-indigo-600 flex flex-row items-center gap-2">
          <UserCircle/>Profiles</a>
      </nav>

      <!-- Logout -->
      <nav class="flex flex-col space-y-6 pl-20 pt-60">
        <a
          href="#"
          @click.prevent="showLogoutModal = true"
          class="text-base font-normal text-zinc-800 hover:text-indigo-600"
        >
          Logout
        </a>
      </nav>
    </aside>

    <div class="flex-1 p-8 text-white overflow-y-auto">
      <h2 class="text-2xl font-bold">My Learning</h2>
      <div class="mt-10">
        <h2 class="text-xl font-semibold mb-4">Completed Topics</h2>
        <div v-if="userProgress.length">
          <div
            v-for="(course, cIndex) in userProgress"
            :key="cIndex"
            class="mb-6"
          >
            <div class="flex justify-between items-center">
              <h3 class="text-lg text-white font-semibold mb-2">{{ course.name }}</h3>
              <div class="text-right">
                <div>
                  {{
                    course.name === 'Vue'
                      ? vueProgress
                      : course.name === 'Laravel'
                        ? laravelProgress
                        : course.progress
                  }}%
                </div>
                <div class="w-52 h-6 rounded-md mb-5 bg-gray-300 overflow-hidden">
                  <div
                    class="h-6 rounded-md"
                    :style="{
                      width: (course.name === 'Vue'
                        ? vueProgress
                        : course.name === 'Laravel'
                          ? laravelProgress
                          : course.progress
                      ) + '%',
                      background: course.name === 'Vue'
                        ? 'linear-gradient(to right, #288feb, #42b0ff)'
                        : course.name === 'Laravel'
                          ? 'linear-gradient(to right, #246242, #4AC887)'
                          : 'linear-gradient(to right, #6b7280, #9ca3af)'
                    }"
                  ></div>
                </div>
              </div>
            </div>
            <div v-if="course.passedTopics.length" class="flex flex-col gap-3">
              <div v-for="(topic, index) in course.passedTopics" :key="topic.id" class="bg-gray-50 p-4 rounded-lg shadow hover:bg-green-100 transition flex justify-between items-center">
                <div>
                  <h4 class="text-black font-medium text-md mb-1">{{ topic.title }}</h4>
                  <p class="text-black text-sm">Score: {{ topic.score ?? 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm" :class="topic.passed ? 'text-black' : 'text-red-300'" >
                    Status: {{ topic.passed ? 'Passed' : 'Failed' }}
                  </p>
                </div>
              </div>
            </div>
            <p v-else class="text-gray-400 text-sm">No completed topics yet.</p>
          </div>
        </div>
        <p v-else class="mt-6 text-gray-400">You haven't started any courses yet.</p>
      </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div
      v-if="showLogoutModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm text-center text-gray-800">
        <h2 class="text-xl font-semibold mb-4">Are you sure?</h2>
        <p class="mb-6">Do you really want to logout from your account?</p>
        <div class="flex justify-center space-x-4">
          <button
            @click="confirmLogout"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
          >
            Yes, Logout
          </button>
          <button
            @click="showLogoutModal = false"
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'
import { LayoutDashboard , BookCopyIcon, BookCheckIcon, TerminalSquareIcon, BadgeCheckIcon, UserCircle } from 'lucide-vue-next';

const header = "</HORIZON>"
export default {
  props: {
    auth: Object
  },
  data() {
    return {
      userProgress: [],
      showLogoutModal: false,
      vueProgress: 0,
      laravelProgress: 0
    }
  },
  mounted() {
    this.fetchProgress()
    this.getPercentage()
  },
  methods: {
    async fetchProgress() {
      try {
        const userId = this.auth.user.id;
        const response = await axios.get(`/api/user-attempted-topics/${userId}`);
        const topics = Object.values(response.data);

        const groupedByCourse = {};

        topics.forEach(topic => {
          const courseName = topic.course_name || 'Untitled Course';
          const courseId = topic.course_id;

          if (!groupedByCourse[courseId]) {
            groupedByCourse[courseId] = {
              name: courseName,
              courseId,
              passedTopics: []
            };
          }

          groupedByCourse[courseId].passedTopics.push({
            id: topic.topic_id,
            title: topic.title,
            score: topic.score,
            passed: topic.passed
          });
        });

        this.userProgress = Object.values(groupedByCourse).map(course => {
          const total = course.passedTopics.length;
          const passed = course.passedTopics.filter(t => t.passed).length;
          return {
            ...course,
            progress: total ? Math.round((passed / total) * 100) : 0
          };
        });
      } catch (error) {
        console.error('Failed to load progress:', error);
      }
    },

    async getPercentage() {
      try {
        const userId = this.auth.user.id;
        const res = await fetch(`/api/user-progress/${userId}`)
        const data = await res.json()

        const vue = data.progress_by_course.find(course => course.course_name === 'Vue')
        const laravel = data.progress_by_course.find(course => course.course_name === 'Laravel')

        this.vueProgress = vue ? vue.overall_progress : 0
        this.laravelProgress = laravel ? laravel.overall_progress : 0

      } catch (error) {
        console.error('Failed to fetch course progress:', error)
      }
    },
    resumeCourse(courseId, topicId) {
      router.visit(`/courses/${courseId}/topics/${topicId}`)
    },

    confirmLogout() {
      router.post('/logout')
    }
  }
}
</script>

<script setup>
import { usePage } from '@inertiajs/vue3'
const { auth } = usePage().props
const user = auth.user
</script>
