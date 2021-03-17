<template>
  <default-field :field="field" :errors="errors" :full-width-content="true">
    <template slot="field">
      <div class="bg-white rounded-lg">
        <draggable v-model="images" group="image-group" v-on:start="drag=true" v-on:end="drag=false" :class="`flex flex-wrap mb-2 laravel-eloquent-imagery-${this.resourceName}`">
          <div v-for="(image, index) in images" :class="`pl-1 pr-1 border border-70 flex items-end m-1 laravel-eloquent-imagery-image-${(index + 1)}`">
              <image-card-input :isReadonly="isReadonly" v-bind:image.sync="image" v-on:remove-image="removeImage"></image-card-input>
          </div>

          <!--<button v-on:click.prevent="debugThis">De</button>-->

          <div v-show="!isReadonly"  v-if="(isCollection == false && images.length == 0) || isCollection" slot="footer" class="flex items-center pl-1 pr-1 m-1 border border-70">
            <div class="content-center px-6 py-4">
              <input
                ref="addNewImageFileInput"
                class="select-none form-file-input"
                type="file"
                v-bind:id="`eloquent-imagery-` + this.field.name + `-add-image`"
                name="name"
                v-on:change="addImage"
              />

              <span class="cursor-pointer" v-on:click="() => this.$refs['addNewImageFileInput'].click()">
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

  import store from './store'

  export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    components: {
      ImageCardInput,
      Draggable
    },

    computed: {
      images: {
        get () {
          return this.$store.getters[`eloquentImagery/${this.field.attribute}/getImages`]
        },
        set (value) {
          this.$store.commit(`eloquentImagery/${this.field.attribute}/updateImages`, value)
        }
      },

      isCollection () {
        return this.$store.getters[`eloquentImagery/${this.field.attribute}/getIsCollection`]
      }
    },

    methods: {
      debugThis () {
        console.log(this.images)
      },

      setInitialValue () {
        let isCollection = this.field.isCollection

        let images = (isCollection ? this.field.value.images : (this.field.value ? [this.field.value] : []))
          .map((image, i) => {
            return {
              inputId: 'eloquent-imagery-' + this.field.attribute.replace('_', '-') + '-' + i,
              previewUrl: image.previewUrl,
              thumbnailUrl: image.thumbnailUrl,
              path: image.path,
              metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
            }
          })

        this.$store.commit(`eloquentImagery/${this.field.attribute}/initialize`, { fieldName: this.field.attribute, isCollection, images })
      },

      addImage (event, metadata = {}) {
        this.$store.dispatch(`eloquentImagery/${this.field.attribute}/addImageFromFile`, {
          file: event.target.files[0]
        })
      },

      removeImage (image) {
        this.$store.dispatch(`eloquentImagery/${this.field.attribute}/removeImage`, image)
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
      }
    },

    created () {
      this.$store.registerModule(`eloquentImagery/${this.field.attribute}`, store)
    },

    destroyed () {
      this.$store.unregisterModule(`eloquentImagery/${this.field.attribute}`)
    }
  }
</script>
