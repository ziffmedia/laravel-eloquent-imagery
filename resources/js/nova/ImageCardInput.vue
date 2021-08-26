<template>
  <div class="px-2 py-2">
    <img style="max-height: 100px" class="block mx-auto mb-2 sm:mb-0 sm:mr-4 sm:ml-0"
      v-bind:src="image.thumbnailUrl"
      v-on:click.prevent="openPreviewImageModal"
    />

    <portal to="modals" v-if="previewImageModalOpen">
      <modal @modal-close="handleClickaway">
        <img class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0" v-bind:src="image.previewUrl" />
      </modal>
    </portal>

    <div v-show="!isReadonly">
      <input
        ref="replaceImageFileInput"
        class="select-none form-file-input"
        type="file"
        :id="image.inputId"
        @change="fileChange"
      />

      <div class="flex">
        <div class="flex-1 text-center cursor-pointer" v-on:click="() => this.$refs['replaceImageFileInput'].click()">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
            <path d="M13 5.41V17a1 1 0 0 1-2 0V5.41l-3.3 3.3a1 1 0 0 1-1.4-1.42l5-5a1 1 0 0 1 1.4 0l5 5a1 1 0 1 1-1.4 1.42L13 5.4zM3 17a1 1 0 0 1 2 0v3h14v-3a1 1 0 0 1 2 0v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3z"/>
          </svg>
        </div>
        <div class="flex-1 text-center cursor-pointer" v-on:click.prevent="openMetadataModal">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
            <path d="M2.59 13.41A1.98 1.98 0 0 1 2 12V7a5 5 0 0 1 5-5h4.99c.53 0 1.04.2 1.42.59l8 8a2 2 0 0 1 0 2.82l-8 8a2 2 0 0 1-2.82 0l-8-8zM20 12l-8-8H7a3 3 0 0 0-3 3v5l8 8 8-8zM7 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
          </svg>
        </div>
        <div class="flex-1 text-center cursor-pointer" v-on:click.prevent="$emit('remove-image', image)">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
            <path d="M16.24 14.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 0 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12l2.83 2.83z"/>
          </svg>
        </div>
      </div>

      <div class="mt-1 text-danger-dark text-xs" v-if="isMissingMetadata">
        *Missing required metadata
      </div>

      <portal to="modals" v-if="metadataModalOpen">
        <image-metadata-modal :isReadonly="isReadonly" v-bind:image.sync="image" @modalClose="handleClickaway"></image-metadata-modal>
      </portal>
    </div>
  </div>
</template>

<script>
  import ImageMetadataModal from './ImageMetadataModal'

  export default {
    props: ['image', 'isReadonly'],

    components: {
      ImageMetadataModal
    },

    data () {
      return {
        metadataModalOpen: false,
        previewImageModalOpen: false
      }
    },

    computed: {
      isMissingMetadata() {
          let missingAltText = this.image.metadata.findIndex(item => (item.key === 'altText' && item.value != '')) === -1
          let missingAttribution = this.image.metadata.findIndex(item => (item.key === 'attribution' && item.value != '')) === -1
          return missingAltText || missingAttribution
      }
    },

    methods: {
      fileChange (event) {

        let file = event.target.files[0]

        this.image.previewUrl = this.image.thumbnailUrl = URL.createObjectURL(file)

        let reader = new FileReader()

        reader.addEventListener('load', () => {
          this.image.fileData = reader.result
        })

        reader.readAsDataURL(file)
      },

      openPreviewImageModal (event) {
        this.previewImageModalOpen = true
      },

      openMetadataModal (event) {
        this.metadataModalOpen = true
      },

      handleClickaway () {
        this.metadataModalOpen = false
        this.previewImageModalOpen = false
      },
    }
  }
</script>
