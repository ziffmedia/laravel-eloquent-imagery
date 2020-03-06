<template>
  <default-field :field="field" :errors="errors" full-width-content="true">
    <template slot="field">
      <div class="bg-white rounded-lg">

        <div v-if="!field.isCollection" class="flex flex-wrap mb-2 laravel-eloquent-imagery-articles">
          <AddImageButton v-if="images.length == 0" v-on:image-added="addImage" />

          <div v-if="images.length == 1" class="pl-1 pr-1 border border-70 flex items-end m-1">
            <image-card-input v-bind:image.sync="images[0]" v-on:remove-image="removeImage"></image-card-input>
          </div>
        </div>

        <div v-if="field.isCollection">
          <draggable v-model="images" group="image-group" v-on:start="drag=true" v-on:end="drag=false" :class="`flex flex-wrap mb-2 laravel-eloquent-imagery-${this.resourceName}`">
            <div v-for="(image, index) in images" :class="`pl-1 pr-1 border border-70 flex items-end m-1 laravel-eloquent-imagery-image-${(index + 1)}`">
              <image-card-input v-bind:image.sync="image" v-on:remove-image="removeImage"></image-card-input>
            </div>

            <AddImageButton v-on:image-added="addImage" />
          </draggable>
        </div>

      </div>

    </template>
  </default-field>
</template>

<script>
  import { FormField, HandlesValidationErrors, Errors } from 'laravel-nova'
  import Draggable from 'vuedraggable'

  // component dependnecies
  import AddImageButton from './AddImageButton'
  import ImageCardInput from './ImageCardInput'

  export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    components: {
      AddImageButton,
      Draggable,
      ImageCardInput
    },

    data () {
      return {
        images: [],
      }
    },

    methods: {
      setInitialValue () {
        let images = (this.field.isCollection) ? this.field.value.images : (this.field.value ? [this.field.value] : [])

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

        formData.append(this.field.attribute, JSON.stringify(this.field.isCollection ? serializedImages : serializedImages.pop()))
      },

    }
  }
</script>
