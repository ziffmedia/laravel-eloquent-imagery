<template>
  <default-field :field="field" :errors="errors">
    <template slot="field">
      <div class="bg-white rounded-lg">
        <draggable v-model="images" group="image-group" @start="drag=true" @end="drag=false" class="flex flex-wrap mb-2">
          <div v-for="image in images" class="flex-no-grow pl-1 pr-1">
              <image-card-input v-bind:image.sync="image" v-on:remove-image="removeImage"></image-card-input>
          </div>
          <div slot="footer" class="flex-no-grow pl-1 pr-1">
            <div v-if="(isCollection == false && images.length == 0) || isCollection" class="px-6 py-4 border border-70 h-full content-center">
              <input
                ref="addNewImageFileInput"
                class="form-file-input select-none"
                type="file"
                :id="`eloquent-imagery-` + this.field.name + `-add-image`"
                name="name"
                @change="addImage"
              />

              <span v-on:click="() => this.$refs['addNewImageFileInput'].click()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height="72" width="72">
                  <path fill="#888" d="M6 2h9a1 1 0 0 1 .7.3l4 4a1 1 0 0 1 .3.7v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2zm9 2.41V7h2.59L15 4.41zM18 9h-3a2 2 0 0 1-2-2V4H6v16h12V9zm-5 4h2a1 1 0 0 1 0 2h-2v2a1 1 0 0 1-2 0v-2H9a1 1 0 0 1 0-2h2v-2a1 1 0 0 1 2 0v2z"/>
                </svg>
            </span>
            </div>
          </div>
        </draggable>
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
            thumbnailUrl: image.thumbnailUrl,
            path: image.path,
            metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
          }
        })
      },

      addImage (event, metadata = {}) {

        let file = event.target.files[0]

        let imageUrl = URL.createObjectURL(file)

        let image = {
          inputId: 'eloquent-imagery-' + this.field.name + '-' + (this.images.length + 1),
          previewUrl: imageUrl,
          thumbnailUrl: imageUrl,
          metadata: Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]}))
        }

        this.images.push(image)

        return new Promise((resolve, reject) => {
          let reader = new FileReader()

          reader.addEventListener('load', () => {
            image.fileData = reader.result

            resolve(image)
          })

          reader.readAsDataURL(file)
        })
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
