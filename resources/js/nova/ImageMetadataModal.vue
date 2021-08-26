<template>
  <modal>
    <div class="w-screen">
      <div class="w-2/3 m-auto bg-white select-text" style="min-height: 12em">
        <div class="w-full p-8 m-2">

          <h3 class="mb-2">Image Metadata</h3>

          <div class="w-full flex items-center">
            <div class="w-1/6 pr-2 text-right font-bold">Alt Text:<span class="text-danger-dark">*</span></div>
            <div class="w-5/6">
              <input required="required" type="text" class="w-full text-xs form-control form-input form-input-bordered my-1" v-model="image.metadata[getMetadataIndex('altText')].value" :readonly="isReadonly"/>
            </div>
          </div>

          <div class="w-full flex items-center">
            <div class="w-1/6 pr-2 text-right font-bold">Attribution:<span class="text-danger-dark">*</span></div>
            <div class="w-5/6">
              <input required="required" type="text" class="w-full text-xs form-control form-input form-input-bordered my-1" v-model="image.metadata[getMetadataIndex('attribution')].value" :readonly="isReadonly"/>
            </div>
          </div>

          <div class="w-full flex items-center">
            <div class="w-1/6 pr-2 text-right font-bold">Caption:</div>
            <div class="w-5/6">
              <input type="text" class="w-full text-xs form-control form-input form-input-bordered my-1" v-model="image.metadata[getMetadataIndex('caption')].value" :readonly="isReadonly"/>
            </div>
          </div>

          <div class="flex" v-for="(metadata, index) in image.metadata" v-if="!['altText', 'attribution', 'caption'].includes(image.metadata[index].key)">
            <input type="text" class="w-1/3 text-xs form-control form-input form-input-bordered m-1" v-model="image.metadata[index].key" :readonly="isReadonly"/>
            <input type="text" class="w-full text-xs form-control form-input form-input-bordered m-1" v-model="image.metadata[index].value" :readonly="isReadonly"/>
            <span class="cursor-pointer my-2 ml-2" v-on:click.prevent="removeMetadata(image, index)" v-if="!isReadonly">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                <path class="heroicon-ui" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2zm0 2v14h14V5H5zm11 7a1 1 0 0 1-1 1H9a1 1 0 0 1 0-2h6a1 1 0 0 1 1 1z"/>
              </svg>
            </span>
          </div>

          <button class="flex items-center mx-auto mt-2" v-on:click.prevent="addMetadata(image)" v-if="!isReadonly">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
              <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2zm0 2v14h14V5H5zm8 6h2a1 1 0 0 1 0 2h-2v2a1 1 0 0 1-2 0v-2H9a1 1 0 0 1 0-2h2V9a1 1 0 0 1 2 0v2z"/>
            </svg>
            <span class="ml-2">Add Metadata</span>
          </button>

          <div class="text-right mt-2">
            <button v-if="isReadonly" class="btn btn-link dim cursor-pointer text-80 ml-auto mr-6" v-on:click.prevent="closeModal">
              Close
            </button>
            <button v-if="!isReadonly" class="btn btn-link dim cursor-pointer text-80 ml-auto mr-6" v-on:click.prevent="handleCancelMetadataUpdate(image)">
              Cancel
            </button>
            <button v-if="!isReadonly" class="btn btn-default btn-primary inline-flex items-center relative" v-on:click.prevent="closeModal">
              Update &amp; Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </modal>
</template>

<script>
    export default {
        props: ['image', 'isReadonly'],

        data () {
            return {
                originalMetadata: null,
            }
        },

        created () {
            this.originalMetadata = JSON.parse(JSON.stringify(this.image.metadata)) // Clone metadata to preserve state

            if (this.getMetadataIndex('altText') === -1) {
                this.image.metadata.push({key: 'altText', value: ''})
            }
            if (this.getMetadataIndex('attribution') === -1) {
                this.image.metadata.push({key: 'attribution', value: ''})
            }
            if (this.getMetadataIndex('caption') === -1) {
                this.image.metadata.push({key: 'caption', value: ''})
            }
        },

        methods: {
            closeModal () {
                this.$emit('modalClose', true)
            },

            handleCancelMetadataUpdate (image) {
                image.metadata = this.originalMetadata
                this.closeModal()
            },

            addMetadata (image) {
                image.metadata.push({key: '', value: ''})
            },

            removeMetadata (image, index) {
                image.metadata.splice(index, 1)
            },

            getMetadataIndex (objectKey) {
                return this.image.metadata.findIndex(item => item.key === objectKey)
            },
        }
    }
</script>
