<template>
  <div class="flex h-screen w-full flex-wrap">
    <!-- Main Area -->
    <main class="flex-1 p-6 overflow-y-auto">
      <h2 class="text-2xl font-bold mb-4">Sandpit</h2>

      <!-- Tabs -->
      <div class="mb-2 flex space-x-4 border-b">
        <button
          v-for="tab in visibleTabs"
          :key="tab"
          @click="activeTab = tab"
          :class="[
            'py-1 px-4 border-b-2',
            activeTab === tab ? 'border-indigo-500 font-semibold' : 'border-transparent',
          ]"
        >
          {{ tab }}
        </button>
      </div>

      <!-- Editor -->
      <div v-if="activeTab !== 'instruction'" class="h-[400px] rounded-md border">
        <div ref="editorContainer" class="h-full w-full"></div>
      </div>
      <div
        v-else
        class="h-[400px] overflow-y-auto rounded-md border bg-gray-100 p-4 font-mono text-sm"
      >
        <pre>{{ codeSections.instruction }}</pre>
      </div>

      <p class="mt-2 text-sm text-gray-600">Lines: {{ lineCount }} / 3000</p>

      <!-- Run -->
      <div class="mt-3">
        <button
          @click="runCode"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Run
        </button>
      </div>

      <!-- Error -->
      <div v-if="error" class="mt-4 text-red-500 font-mono bg-red-100 p-2 rounded">
        {{ error }}
      </div>

      <!-- Result -->
      <div class="mt-6">
        <h3 class="text-lg font-semibold mb-2">Result:</h3>
        <iframe :srcdoc="compiledHtml" class="w-full h-[300px] border rounded-md"></iframe>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import loader from '@monaco-editor/loader'

/**
 * mode prop (vue | laravel)
 */
const props = defineProps({
  mode: {
    type: String,
    default: 'vue',
  },
})

const activeTab = ref('template')

const tabs = {
  vue: ['template', 'script', 'style', 'instruction'],
  laravel: ['template', 'web.php', 'style', 'instruction'],
}
const visibleTabs = computed(() => tabs[props.mode])

const codeSections = ref({
  template: '',
  script: '',
  style: '',
  'web.php': '',
  instruction: '# Instructions will appear here',
})

const compiledHtml = ref('')
const error = ref('')
const editorContainer = ref(null)
let editorInstance = null

const editorLanguage = computed(() => {
  if (activeTab.value === 'template') return 'html'
  if (activeTab.value === 'style') return 'css'
  if (activeTab.value === 'script') return 'javascript'
  if (activeTab.value === 'web.php') return 'php'
  return 'plaintext'
})

onMounted(async () => {
  const monaco = await loader.init()
  editorInstance = monaco.editor.create(editorContainer.value, {
    value: codeSections.value[activeTab.value],
    language: editorLanguage.value,
    theme: 'vs-dark',
    automaticLayout: true,
    fontSize: 14,
    minimap: { enabled: false },
  })
  editorInstance.onDidChangeModelContent(() => {
    codeSections.value[activeTab.value] = editorInstance.getValue()
  })
})

watch([activeTab, editorLanguage], async ([newTab, newLang]) => {
  if (!editorInstance) return
  const monaco = await loader.init()
  const model = editorInstance.getModel()
  editorInstance.setValue(codeSections.value[newTab] || '')
  monaco.editor.setModelLanguage(model, newLang)
})

function parseWebPhp(phpCode) {
  const lines = phpCode.split('\n')
  const data = {}
  for (let line of lines) {
    line = line.trim()
    if (line.startsWith('$')) {
      let [key, value] = line.split('=')
      key = key.replace('$', '').trim()
      value = value.trim().replace(/;$/, '')
      try {
        if (value.startsWith('"') || value.startsWith("'")) {
          data[key] = value.replace(/^["']|["']$/g, '')
        } else if (value.startsWith('[')) {
          data[key] = eval(value)
        } else {
          data[key] = eval(value)
        }
      } catch (e) {
        console.warn('Failed parsing:', line)
      }
    }
  }
  return data
}

function runCode() {
  error.value = ''
  try {
    if (props.mode === 'vue') {
      compiledHtml.value = `
        <html>
          <head>
            <style>${codeSections.value.style}</style>
          </head>
          <body>
            <div id="app">
              ${codeSections.value.template}
            </div>
            <script>
              ${codeSections.value.script}
            <\/script>
          </body>
        </html>`
    } else if (props.mode === 'laravel') {
      const data = parseWebPhp(codeSections.value['web.php'])
      let rendered = codeSections.value.template

      rendered = rendered.replace(/\{\{\s*(\w+)\s*\}\}/g, (_, key) => data[key] ?? '')

      rendered = rendered.replace(
        /@if\s*\((.*?)\)([\s\S]*?)@else([\s\S]*?)@endif/g,
        (_, condition, ifTrue, elseBlock) => {
          const expr = condition.replace(/\$(\w+)/g, (_, v) => JSON.stringify(data[v] ?? null))
          return eval(expr) ? ifTrue : elseBlock
        }
      )

      rendered = rendered.replace(
        /@foreach\s*\(\s*\$(\w+)\s+as\s+\$(\w+)\s*\)([\s\S]*?)@endforeach/g,
        (_, list, item, content) => {
          const arr = data[list]
          if (!Array.isArray(arr)) return ''
          return arr
            .map((val) =>
              content.replace(new RegExp(`\\{\\{\\s*${item}\\s*\\}\\}`, 'g'), val)
            )
            .join('')
        }
      )

      compiledHtml.value = `
        <html>
          <head>
            <style>${codeSections.value.style}</style>
          </head>
          <body>
            ${rendered}
          </body>
        </html>`
    }
  } catch (err) {
    error.value = 'Error: ' + err.message
  }
}

const lineCount = computed(() => {
  const code = codeSections.value[activeTab.value] || ''
  return code.split('\n').length
})
</script>
