<template>
  <div class="flex px-6 py-4">
    <div>
      <img style="max-height: 80px" class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0" :src="image.previewUrl">
      <div class="flex -mx-3">
        <input
          class="form-file-input select-none"
          type="file"
          :id="image.inputId"
          @change="fileChange"
        />
        <label
          :for="image.inputId"
          class="text-xs rounded-full mt-1 px-1 py-1 leading-normal border border-primary text-primary hover:bg-primary hover:text-white"
        >
          Replace
        </label>

        <button class="text-xs rounded-full mt-1 ml-1 px-1 py-1 leading-normal border border-danger text-danger hover:bg-danger hover:text-white" v-on:click.prevent="$emit('remove-image', image)">
          Remove
        </button>

      </div>
    </div>
    <div class="w-full">
      <span class="text-sm leading-tight text-grey-dark">
          Image Metadata:
      </span>
      <div class="flex -mx-3 px-3" v-for="(metadata, index) in image.metadata">
        <input
          type="text"
          class="w-1/3 text-xs form-control form-input form-input-bordered"
          v-model="image.metadata[index].key"
        />
        <input
          type="text"
          class="w-full text-xs form-control form-input form-input-bordered"
          v-model="image.metadata[index].value"
        />
        <button class="text-xs rounded-full px-1 py-1 leading-normal bg-white border border-danger text-danger hover:bg-danger hover:text-white" v-on:click.prevent="removeMetadata(image, index)">
          x
        </button>
      </div>
      <div class="mt-1 text-right">
        <button class="text-xs rounded-full px-4 py-1 leading-normal bg-white border border-primary text-primary hover:bg-primary hover:text-white" v-on:click.prevent="addMetadata(image)" >
          Add Metadata Row
        </button>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    props: ['image'],

    methods: {
      fileChange (event) {

        let file = event.target.files[0]

        console.log(this.image, file)

        this.image.previewUrl = URL.createObjectURL(file)

        console.log(this.image.previewUrl)

        let reader = new FileReader()

        reader.addEventListener('load', () => {
          this.image.fileData = reader.result
        })

        reader.readAsDataURL(file)

        console.log(this.image.previewUrl)
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