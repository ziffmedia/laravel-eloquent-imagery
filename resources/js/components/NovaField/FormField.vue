<template>
  <default-field :field="field" :errors="errors">
    <template slot="field">
      <div class="bg-white shadow-lg rounded-lg">
        <draggable v-model="images" group="image-group" @start="drag=true" @end="drag=false">
          <div v-for="image in images">
              <image-card-input v-bind:image.sync="image" v-on:remove-image="removeImage"></image-card-input>
          </div>
        </draggable>
        <div v-if="(isCollection == false && images.length == 0) || isCollection">
          <input
            class="form-file-input select-none"
            type="file"
            :id="`eloquent-imagery-` + this.field.name + `-add-image`"
            name="name"
            @change="addImage"
          />
          <label
            :for="`eloquent-imagery-` + this.field.name + `-add-image`"
            class="text-xs rounded-full mt-1 px-1 py-1 leading-normal border border-primary text-primary hover:bg-primary hover:text-white"
          >
            Add Image
          </label>
        </div>
      </div>

    </template>
  </default-field>
</template>

<script>
  import { FormField, HandlesValidationErrors, Errors } from 'laravel-nova'
  import Draggable from 'vuedraggable'
  import ImageCardInput from './ImageCardInput'

  export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    components: {
      ImageCardInput,
      Draggable
    },

    data: () => ({
      isCollection: false,
      images: [],
    }),

    methods: {
      setInitialValue () {
        this.isCollection = this.field.isCollection

        let images = (this.isCollection) ? this.field.value.images : (this.field.value ? [this.field.value] : [])

        this.images = images.map((image, i) => {
          return {
            inputId: 'eloquent-imagery-' + this.field.name + '-' + i,
            previewUrl: image.previewUrl,
            path: image.path,
            metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
          }
        })
      },

      addImage (event) {

        let file = event.target.files[0]

        let image = {
          inputId: 'eloquent-imagery-' + this.field.name + '-' + (this.images.length + 1),
          previewUrl: URL.createObjectURL(file),
          metadata: []
        }

        this.images.push(image)

        let reader = new FileReader()

        reader.addEventListener('load', () => {
          image.fileData = reader.result
        })

        reader.readAsDataURL(file)
      },

      removeImage (imageToRemove) {
        this.images = this.images.filter(image => image !== imageToRemove)
      },

      fill (formData) {
        let serializedImages = this.images.map(image => ({
          fileData: (image.hasOwnProperty('fileData') ? image.fileData : null),

          path: (image.hasOwnProperty('path') ? image.path : null),

          metadata: image.metadata.reduce((object, next) => {
            object[next.key] = next.value
            return object
          }, {})
        }))

        formData.append(this.field.attribute, JSON.stringify(this.isCollection ? serializedImages : serializedImages.pop()))
      },

    }
  }
</script>
