<template>
  <default-field
    :field="field"
    :errors="errors"
    :full-width-content="true"
    :show-help-text="field.helpText != null"
  >
    <template slot="field">
      <div :class="`bg-white rounded-lg laravel-eloquent-imagery-${resourceName}`">
        <template v-if="field.isCollection">
          <draggable
            :value="imageCollection"
            class="flex flex-wrap mb-2"
            group="image-group"
            @update="updateImageCollectionOrder"
          >
            <div
              v-for="(image, index) in imageCollection"
              :key="index"
              :class="`border border-70 flex items-end m-1 laravel-eloquent-imagery-image-${(index + 1)}`"
            >
              <form-image-card
                :image="image"
                @remove-image="removeImageFromImageCollection(image)"
              />
            </div>
          </draggable>
        </template>
        <template v-else>
          <form-image-card
            :image="field.image"
            @remove-image="removeImage"
          />
        </template>

        <div class="content-center px-6 py-4">
          <input
            :id="`eloquent-imagery-` + field.name + `-add-image`"
            ref="uploadNewImageFromFileInput"
            class="select-none form-file-input"
            type="file"
            name="name"
            @change="uploadNewImageFromFileInput($event.target.files[0])"
          />

          <span
            class="cursor-pointer"
            @click.prevent="$refs['uploadNewImageFromFileInput'].click()"
          >
            <!-- eslint-disable vue/max-attributes-per-line -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height="72" width="72">
              <path fill="#888" d="M6 2h9a1 1 0 0 1 .7.3l4 4a1 1 0 0 1 .3.7v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2zm9 2.41V7h2.59L15 4.41zM18 9h-3a2 2 0 0 1-2-2V4H6v16h12V9zm-5 4h2a1 1 0 0 1 0 2h-2v2a1 1 0 0 1-2 0v-2H9a1 1 0 0 1 0-2h2v-2a1 1 0 0 1 2 0v2z"/>
            </svg>
            <!-- eslint-enable -->
          </span>
        </div>
      </div>
    </template>
  </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import Draggable from 'vuedraggable'
import FormImageCard from './FormImageCard'

import imageCollectionStore from './image-collection-store'

export default {
  components: {
    FormImageCard,
    Draggable
  },

  mixins: [
    FormField,
    HandlesValidationErrors
  ],

  props: {
    resourceName: {
      type: String,
      required: true
    },
    resourceId: {
      type: String,
      required: true
    },
    field: {
      type: String,
      required: true
    }
  },

  computed: {
    imageCollection: {
      get () {
        return this.$store.getters[`eloquentImagery/${this.field.attribute}/getImages`]
      },
      set (value) {
        this.$store.commit(`eloquentImagery/${this.field.attribute}/updateImages`, value)
      }
    }
  },

  created () {
    if (this.field.isCollection) {
      this.$store.registerModule(`eloquentImagery/${this.field.attribute}`, imageCollectionStore)
      this.$store.commit(`eloquentImagery/${this.field.attribute}/initialize`, { isReadOnly: false, fieldName: this.field.attribute, images: this.field.value.images })
    }
  },

  destroyed () {
    if (this.field.isCollection) {
      this.$store.unregisterModule(`eloquentImagery/${this.field.attribute}`)
    }
  },

  methods: {
    fill (formData) {
      const value = (this.field.isCollection)
        ? this.$store.getters[`eloquentImagery/${this.field.attribute}/serialize`]
        : this.image

      debugger

      formData.append(this.field.attribute, JSON.stringify(value))
    },

    removeImageFromImageCollection (image) {
      this.$store.dispatch(`eloquentImagery/${this.field.attribute}/removeImage`, image)
    },

    removeImage () {
      console.log('just remove the image please')
    },

    updateImageCollectionOrder (dragEvent) {
      console.log('todo, update order', dragEvent)
    },

    uploadNewImageFromFileInput (file) {
      if (this.field.isCollection) {
        this.$store.dispatch(`eloquentImagery/${this.field.attribute}/addImageFromFile`, { file })
        return
      }

      console.log('update image')
    }
  }
}
</script>
