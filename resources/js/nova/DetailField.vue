<template>
  <panel-item :field="field">
    <template slot="value">
      <p v-if="(field.isCollection && field.value.images.length === 0) || (!field.isCollection && field.value === null)">
        —
      </p>

      <div :class="`flex flex-wrap mb-2 laravel-eloquent-imagery-${resourceName}`">
        <template v-if="field.isCollection">
          <div
            v-for="(image, index) in imageCollection"
            :key="index"
            :class="`border border-70 flex items-end m-1 laravel-eloquent-imagery-image-${(index + 1)}`"
          >
            <image-card
              :editable="false"
              :metadata="image.metadata"
              :metadata-form-configuration="field.metadataFormConfiguration"
              :preview-url="image.previewUrl"
              :thumbnail-url="image.thumbnailUrl"
            />
          </div>
        </template>
        <template v-else>
          <div class="flex flex-wrap mb-2 laravel-eloquent-imagery">
            <image-card
              :editable="false"
              :metadata="singleImage.metadata"
              :metadata-form-configuration="field.metadataFormConfiguration"
              :preview-url="singleImage.previewUrl"
              :thumbnail-url="singleImage.thumbnailUrl"
            />
          </div>
        </template>
      </div>
    </template>
  </panel-item>
</template>

<script>
import ImageCard from './ImageCard'
import createImageCollectionStore from './createImageCollectionStore'

export default {
  components: {
    ImageCard
  },

  props: {
    field: {
      type: Object,
      required: true
    },
    resourceName: {
      type: String,
      required: true
    }
  },

  data () {
    return {
      imageCollection: null,
      singleImage: null
    }
  },

  created () {
    if (this.field.isCollection) {
      this.$store.registerModule(`eloquentImagery/${this.field.attribute}`, createImageCollectionStore())
      this.$store.commit(`eloquentImagery/${this.field.attribute}/initialize`, { fieldName: this.field.attribute, images: this.field.value.images })

      this.imageCollection = this.$store.getters[`eloquentImagery/${this.field.attribute}/getImages`]
    } else {
      this.singleImage = this.field.value
    }
  },

  destroyed () {
    if (this.field.isCollection) {
      this.$store.unregisterModule(`eloquentImagery/${this.field.attribute}`)
    }
  }
}
</script>
