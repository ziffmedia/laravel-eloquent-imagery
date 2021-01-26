<template>
  <portal to="modals" v-if="show">
    <modal>
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-8">
          <heading :level="2" class="mb-6">{{ headerText }}</heading>
          <p class="text-80">
            {{ bodyText }}
          </p>
        </div>
        <div class="bg-30 px-6 py-3 flex">
          <div class="ml-auto">
            <button dusk="cancel-upload-delete-button"
                type="button"
                data-testid="cancel-button"
                v-bind:value="false"
                v-on:click="handleCancel"
                class="btn text-80 font-normal h-9 px-3 mr-3 btn-link"
            >
              {{ __('Cancel') }}
            </button>

            <button v-if="typeof handleConfirm === 'function'"
                dusk="confirm-upload-delete-button"
                ref="confirmButton"
                data-testid="confirm-button"
                v-bind:value="true"
                @click="handleConfirm"
                class="btn btn-default btn-danger"
            >
              {{ __('Upload') }}
            </button>
          </div>
        </div>
      </div>
    </modal>
  </portal>
</template>

<script>

  export default {
    props: {
      show: Boolean,
      headerText: String,
      bodyText: String,
      handleCancel: Function,
      handleConfirm: Function
    }
  }

</script>
