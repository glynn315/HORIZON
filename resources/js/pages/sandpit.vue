<template>
    <div class="flex h-screen w-full flex-wrap">
        <!-- Sidebar -->
        <aside class="flex h-screen w-60 flex-col bg-white">
            <h1 class="w-full p-10 text-center text-2xl !font-bold font-normal text-zinc-800">{{ header }}</h1>
            <div class="flex flex-row items-center gap-3 px-3 py-0">
                <div class="h-16 w-16 overflow-hidden rounded-full border">
                    <img :src="user.photo" alt="" class="h-full w-full" />
                </div>
                <p v-if="user" class="p-2 text-base font-normal text-zinc-800">{{ user.firstname }} {{ user.lastname }}</p>
            </div>
            <nav class="flex flex-col space-y-6 pl-10 pt-14">
                <a href="dashboard" class="flex items-center gap-2 text-base font-normal text-zinc-800 hover:text-indigo-600">
                    <LayoutDashboard /> Dashboard
                </a>
                <a href="/test" class="flex items-center gap-2 text-base font-normal text-zinc-800 hover:text-indigo-600">
                    <BookCopyIcon /> Course
                </a>
                <a href="/mylearning" class="flex items-center gap-2 text-base font-normal text-zinc-800 hover:text-indigo-600">
                    <BookCheckIcon /> My Learning
                </a>
                <a href="/sandpit" class="flex items-center gap-2 text-base font-normal text-zinc-800 hover:text-indigo-600">
                    <TerminalSquareIcon /> Sandpit
                </a>
                <a href="/badges" class="flex items-center gap-2 text-base font-normal text-zinc-800 hover:text-indigo-600">
                    <BadgeCheckIcon /> Badge
                </a>
                <a href="/settings" class="flex items-center gap-2 text-base font-medium text-zinc-800 hover:text-indigo-600">
                    <UserCircle /> Profiles
                </a>
            </nav>
            <!-- Logout -->
            <nav class="flex flex-col space-y-6 pl-20 pt-60">
                <a href="#" @click.prevent="showLogoutModal = true" class="text-base font-normal text-zinc-800 hover:text-indigo-600">Logout</a>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto p-6">
            <h2 class="mb-4 text-2xl font-bold">Sandpit</h2>
            <div class="mb-4">
                <label class="mr-2 font-medium">Language:</label>
                <select v-model="selectedLanguage" class="rounded border p-1">
                    <option value="vue">Vue.js</option>
                    <option value="laravel">Laravel Blade</option>
                </select>
            </div>
            <div class="mb-2 flex space-x-4 border-b">
                <button
                    v-for="tab in visibleTabs"
                    :key="tab"
                    @click="activeTab = tab"
                    :class="['border-b-2 px-4 py-1', activeTab === tab ? 'border-indigo-500 font-semibold' : 'border-transparent']"
                >
                    {{ tab }}
                </button>
            </div>
            <div v-if="activeTab !== 'instruction'" class="h-[400px] rounded-md border">
                <div ref="editorContainer" class="h-full w-full"></div>
            </div>
            <div v-else class="h-[400px] overflow-y-auto rounded-md border bg-gray-100 p-4 font-mono text-sm">
                <pre>{{ codeSections.instruction }}</pre>
            </div>

            <p class="mt-2 text-sm text-gray-600">Lines: {{ lineCount }} / 3000</p>

            <div class="mt-3 flex gap-3">
                <button @click="runCode" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Run</button>
            </div>
            <div v-if="error" class="mt-4 rounded bg-red-100 p-2 font-mono text-red-500">
                {{ error }}
            </div>
            <div class="mt-6">
                <h3 class="mb-2 text-lg font-semibold">Result:</h3>
                <iframe :srcdoc="compiledHtml" class="h-[400px] w-full rounded-md border"></iframe>
            </div>
        </main>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import loader from '@monaco-editor/loader';
import { BadgeCheckIcon, BookCheckIcon, BookCopyIcon, LayoutDashboard, TerminalSquareIcon, UserCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const header = '</HORIZON>';
const { auth } = usePage().props;
const user = auth.user;

const selectedLanguage = ref('vue');
const activeTab = ref('compiler');
const tabs = {
    vue: ['compiler', 'script', 'style', 'instruction'],
    laravel: ['compiler', 'web.php', 'style', 'instruction'],
};
const visibleTabs = computed(() => tabs[selectedLanguage.value]);

const codeSections = ref({
    compiler: '',
    script: '',
    style: '',
    'web.php': '',
    instruction: '# Instructions will appear here',
});

const editorContainer = ref(null);
let editorInstance = null;
const error = ref('');
const compiledHtml = ref('');

const editorLanguage = computed(() => {
    if (activeTab.value === 'compiler') return 'html';
    if (activeTab.value === 'style') return 'css';
    if (activeTab.value === 'script') return 'javascript';
    if (activeTab.value === 'web.php') return 'php';
    return 'plaintext';
});

onMounted(async () => {
    const monaco = await loader.init();
    editorInstance = monaco.editor.create(editorContainer.value, {
        value: codeSections.value[activeTab.value],
        language: editorLanguage.value,
        theme: 'vs-dark',
        automaticLayout: true,
        fontSize: 14,
        minimap: { enabled: false },
    });
    editorInstance.onDidChangeModelContent(() => {
        codeSections.value[activeTab.value] = editorInstance.getValue();
    });
});

watch([activeTab, editorLanguage], async ([newTab, newLang]) => {
    if (!editorInstance) return;
    const monaco = await loader.init();
    const model = editorInstance.getModel();
    editorInstance.setValue(codeSections.value[newTab] || '');
    monaco.editor.setModelLanguage(model, newLang);
});

const lineCount = computed(() => {
    const code = codeSections.value[activeTab.value] || '';
    return code.split('\n').length;
});

function parseWebPhp(phpCode) {
    const lines = phpCode.split('\n');
    const data = {};
    for (let line of lines) {
        line = line.trim();
        if (line.startsWith('$')) {
            let [key, value] = line.split('=');
            key = key.replace('$', '').trim();
            value = value.trim().replace(/;$/, '');
            try {
                if (value.startsWith('"') || value.startsWith("'")) {
                    data[key] = value.replace(/^["']|["']$/g, '');
                } else if (value.startsWith('[')) {
                    data[key] = eval(value);
                } else {
                    data[key] = eval(value);
                }
            } catch (e) {
                console.warn('Failed parsing:', line);
            }
        }
    }
    return data;
}

function runCode() {
    error.value = '';
    try {
        if (selectedLanguage.value === 'vue') {
            compiledHtml.value = `
        <html>
            <head>
                <style>${codeSections.value.style}</style>
            </head>
            <body>
            <div id="app">
                ${codeSections.value.compiler}
            </div>
            <script>
                ${codeSections.value.script}
            <\/script>
            </body>
        </html>`;
        } else if (selectedLanguage.value === 'laravel') {
            const data = parseWebPhp(codeSections.value['web.php']);
            let rendered = codeSections.value.compiler;

            rendered = rendered.replace(/@if\s*\((.*?)\)([\s\S]*?)(@else([\s\S]*?))?@endif/g, (_, condition, ifBlock, elseBlock, elseContent) => {
                try {
                    const expr = condition.replace(/\$(\w+)/g, (_, v) => {
                        return JSON.stringify(data[v] ?? null);
                    });

                    const result = eval(expr);
                    if (result) {
                        return ifBlock;
                    } else {
                        return elseContent || '';
                    }
                } catch (e) {
                    return `<span style="color:red">[Error in @if condition: ${e.message}]</span>`;
                }
            });
            rendered = rendered.replace(/@foreach\s*\(\s*\$(\w+)\s+as\s+\$(\w+)\s*\)([\s\S]*?)@endforeach/g, (_, list, item, content) => {
                const arr = data[list];
                if (!Array.isArray(arr)) return '';

                return arr
                    .map((val) => {
                        return content.replace(/\{\{\s*(\w+)\s*\}\}/g, (__, varName) => {
                            if (varName === item) {
                                return val;
                            }
                            return data[varName] ?? '';
                        });
                    })
                    .join('');
            });
            rendered = rendered.replace(/\{\{\s*(\w+)\s*\}\}/g, (_, key) => data[key] ?? '');
            compiledHtml.value = `
            <html>
                <head>
                    <style>${codeSections.value.style}</style>
                </head>
                <body>
                    ${rendered}
                </body>
            </html>`;
        }
    } catch (err) {
        error.value = 'Error: ' + err.message;
    }
}
</script>
