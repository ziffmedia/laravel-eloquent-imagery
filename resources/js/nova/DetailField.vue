<template>
  <panel-item :field="field">
    <template slot="value">
      <p v-if="(field.isCollection && field.value.images.length === 0) || (!field.isCollection && field.value === null)">
        —
      </p>

      <div :class="`flex flex-wrap mb-2 laravel-eloquent-imagery-${resourceName}`">
        <div
          v-if="!field.isCollection"
          class="flex flex-wrap mb-2 laravel-eloquent-imagery-articles"
        >
          <detail-image-card :image="field.value" />
        </div>
        <div v-else>
          <detail-image-card
            v-for="(image, index) in imageCollection"
            :key="index"
            :image="image"
          />
        </div>
      </div>
    </template>
  </panel-item>
</template>

<script>
import DetailImageCard from './DetailImageCard'
import imageCollectionStore from './image-collection-store'

export default {
  components: {
    DetailImageCard
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
      this.$store.commit(`eloquentImagery/${this.field.attribute}/initialize`, { fieldName: this.field.attribute, images: this.field.value.images })
    }
  },

  destroyed () {
    if (this.field.isCollection) {
      this.$store.unregisterModule(`eloquentImagery/${this.field.attribute}`)
    }
  }

  // computed: {
  //   images () {
  //     const images = (this.field.isCollection) ? this.field.value.images : (this.field.value ? [this.field.value] : [])
  //
  //     return images.map((image, i) => {
  //       return {
  //         inputId: 'eloquent-imagery-' + this.field.attribute + '-' + i,
  //         previewUrl: image.previewUrl,
  //         thumbnailUrl: image.thumbnailUrl,
  //         metadata: Object.keys(image.metadata).map(key => ({ key, value: image.metadata[key] }))
  //       }
  //     })
  //   }
  // }

}
</script>
