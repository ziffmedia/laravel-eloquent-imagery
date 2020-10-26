<template>
  <div class="px-6 py-4">
    <img style="max-height: 80px" class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0"
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

      <portal to="modals" v-if="metadataModalOpen">
        <modal @modal-close="handleClickaway">
          <div class="w-screen">
            <div class="w-2/3 m-auto bg-white select-text" style="min-height: 12em">
              <div class="w-full p-8 m-2">

                <h3>Image Metadata</h3>

                <div class="flex px-3" v-for="(metadata, index) in image.metadata">
                  <input type="text" class="w-1/3 text-xs form-control form-input form-input-bordered m-1" v-model="image.metadata[index].key" />
                  <input type="text" class="w-full text-xs form-control form-input form-input-bordered m-1" v-model="image.metadata[index].value" />
                  <span class="cursor-pointer m-2" v-on:click.prevent="removeMetadata(image, index)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                      <path class="heroicon-ui" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2zm0 2v14h14V5H5zm11 7a1 1 0 0 1-1 1H9a1 1 0 0 1 0-2h6a1 1 0 0 1 1 1z"/>
                    </svg>
                  </span>
                </div>

                <div class="float-right">
                  <span class="cursor-pointer m-2" v-on:click.prevent="addMetadata(image)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                      <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2zm0 2v14h14V5H5zm8 6h2a1 1 0 0 1 0 2h-2v2a1 1 0 0 1-2 0v-2H9a1 1 0 0 1 0-2h2V9a1 1 0 0 1 2 0v2z"/>
                    </svg>
                  </span>
                </div>

                <span class="hover:underline cursor-pointer block m-2" v-on:click.prevent="handleClickaway">
                  Save &amp; Close
                </span>
              </div>
            </div>
          </div>
        </modal>
      </portal>
    </div> <!-- end !isReadonly block -->
  </div>
</template>

<script>
  export default {
    props: ['image', 'isReadonly'],

    data () {
      return {
        metadataModalOpen: false,
        previewImageModalOpen: false
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

      addMetadata (image) {
        image.metadata.push({key: '', value: ''})
      },

      removeMetadata (image, index) {
        image.metadata.splice(index, 1)
      }
    }
  }
</script>
