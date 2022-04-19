<template>
  <modal>
    <div class="w-screen">
      <div
        class="w-2/3 m-auto bg-white select-text"
        style="min-height: 12em"
      >
        <div class="w-full p-8 m-2">
          <h3 class="mb-2">
            Image Metadata
          </h3>

          <div class="w-full flex items-center">
            <div class="w-1/6 pr-2 text-right font-bold">
              Alt Text:
              <span class="text-danger-dark">*</span>
            </div>
            <div class="w-5/6">
              {{ image.metadata[getMetadataIndex('altText')].value }}
            </div>
          </div>

          <div class="w-full flex items-center">
            <div class="w-1/6 pr-2 text-right font-bold">
              Attribution:
              <span class="text-danger-dark">*</span>
            </div>
            <div class="w-5/6">
              {{ image.metadata[getMetadataIndex('attribution')].value }}
            </div>
          </div>

          <div class="w-full flex items-center">
            <div class="w-1/6 pr-2 text-right font-bold">
              Caption:
            </div>
            <div class="w-5/6">
              {{ image.metadata[getMetadataIndex('caption')].value }}
            </div>
          </div>

          <div
            v-for="(metadata, index) in image.metadata.filter(m => !['altText', 'attribution', 'caption'].includes(m.key))"
            :key="index"
            class="flex"
          >
            <div class="w-1/6 pr-2 text-right font-bold">
              {{ metadata.key }}
            </div>
            <div class="w-5/6">
              {{ metadata.value }}
            </div>
          </div>

          <div class="text-right mt-2">
            <button
              class="btn btn-link dim cursor-pointer text-80 ml-auto mr-6"
              @click.prevent="$emit('modalClose', true)"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </modal>
</template>

<script>
export default {
  props: {
    image: {
      type: Object,
      required: true
    }
  },

  methods: {
    getMetadataIndex (objectKey) {
      return this.image.metadata.findIndex(item => item.key === objectKey)
    }
  }
}
</script>
