<template>
  <div class="px-6 py-4">

    <img style="max-height: 80px" class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0"
      :src="this.image.thumbnailUrl"
      v-on:click.prevent="openPreviewImageModal"
    />

    <portal to="modals" v-if="previewImageModalOpen">
      <modal @modal-close="handleClickaway">
        <img class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0" :src="image.previewUrl" />
      </modal>
    </portal>

    <div class="flex">
      <div class="flex-1 text-center" v-on:click.prevent="openMetadataModal">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
          <path d="M2.59 13.41A1.98 1.98 0 0 1 2 12V7a5 5 0 0 1 5-5h4.99c.53 0 1.04.2 1.42.59l8 8a2 2 0 0 1 0 2.82l-8 8a2 2 0 0 1-2.82 0l-8-8zM20 12l-8-8H7a3 3 0 0 0-3 3v5l8 8 8-8zM7 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
        </svg>
      </div>
    </div>

    <portal to="modals" v-if="metadataModalOpen">
      <modal @modal-close="handleClickaway">
        <div class="w-screen">
          <div class="w-2/3 m-auto bg-white select-text" style="min-height: 12em">
            <div class="w-full p-8 m-2">

              <h3>Image Metadata</h3>

              <div class="flex px-3 mb-2" v-for="(metadata, index) in image.metadata">
                <div class="w-1/3 p-2 text-xs border border-70">
                  {{ image.metadata[index].key }}
                </div>
                <div class="w-full p-2 ml-2 text-xs border border-70">
                  {{ image.metadata[index].value }}
                </div>
              </div>

              <span class="hover:underline" v-on:click.prevent="handleClickaway">
                Close
              </span>
            </div>
          </div>
        </div>
      </modal>
    </portal>

  </div>

</template>

<script>
  export default {
    props: ['image'],

    data () {
      return {
        metadataModalOpen: false,
        previewImageModalOpen: false
      }
    },

    methods: {
      openPreviewImageModal (event) {
        this.previewImageModalOpen = true
      },

      openMetadataModal (event) {
        this.metadataModalOpen = true
      },

      handleClickaway () {
        this.metadataModalOpen = false
        this.previewImageModalOpen = false
      }
    }
  }
</script>
