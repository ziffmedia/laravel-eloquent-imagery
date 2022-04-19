<template>
  <div class="px-2 py-2">
    <img
      style="max-height: 100px"
      class="block mx-auto mb-2 sm:mb-0 sm:mr-4 sm:ml-0 cursor-pointer"
      :src="image.thumbnailUrl"
      alt="Image"
      @click.prevent="previewImageModalOpen = true"
    >

    <portal
      v-if="previewImageModalOpen"
      to="modals"
    >
      <modal @modal-close="metadataModalOpen = false; previewImageModalOpen = false">
        <img
          class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0"
          :src="image.previewUrl"
        >
      </modal>
    </portal>

    <div class="flex">
      <div
        class="flex-1 text-center cursor-pointer"
        @click.prevent="metadataModalOpen = true"
      >
        <!-- eslint-disable vue/max-attributes-per-line -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
          <path d="M2.59 13.41A1.98 1.98 0 0 1 2 12V7a5 5 0 0 1 5-5h4.99c.53 0 1.04.2 1.42.59l8 8a2 2 0 0 1 0 2.82l-8 8a2 2 0 0 1-2.82 0l-8-8zM20 12l-8-8H7a3 3 0 0 0-3 3v5l8 8 8-8zM7 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
        </svg>
        <!-- eslint-enable -->
      </div>
    </div>

    <!--<div-->
    <!--  v-if="isMissingMetadata"-->
    <!--  class="mt-1 text-danger-dark text-xs"-->
    <!--&gt;-->
    <!--  *Missing required metadata-->
    <!--</div>-->

    <!--<portal-->
    <!--  v-if="metadataModalOpen"-->
    <!--  to="modals"-->
    <!--&gt;-->
    <!--  <detail-metadata-modal-->
    <!--    :is-readonly="true"-->
    <!--    :image="image"-->
    <!--    @modalClose="handleClickaway"-->
    <!--  />-->
    <!--</portal>-->
  </div>

</template>

<script>
// import DetailMetadataModal from './DetailMetadataModal'

export default {
  components: {
    // DetailMetadataModal
  },

  props: {
    image: {
      type: Object,
      required: true
    }
  },

  data () {
    return {
      metadataModalOpen: false,
      previewImageModalOpen: false
    }
  },

  computed: {
    // isMissingMetadata() {
    //   let missingAltText = this.image.metadata.findIndex(item => (item.key === 'altText' && item.value != '')) === -1
    //   let missingAttribution = this.image.metadata.findIndex(item => (item.key === 'attribution' && item.value != '')) === -1
    //   return missingAltText || missingAttribution
    // }
  }
}
</script>
