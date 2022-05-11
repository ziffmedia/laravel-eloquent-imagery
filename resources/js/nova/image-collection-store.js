
export default {
  namespaced: true,

  state: () => ({
    fieldName: '',
    isReadonly: true,
    images: []
  }),

  mutations: {
    initialize (state, payload) {
      state.isReadonly = payload.isReadOnly ?? true
      state.fieldName = payload.fieldName

      state.images = payload.images.map((image, i) => {
        return {
          ...image,
          id: 'eloquent-imagery-' + payload.fieldName + '-' + (i + 1),
          // ensure empty metadata is in fact an object
          metadata: Array.isArray(image.metadata) ? Object.fromEntries(image.metadata) : image.metadata
        }
      })
    },

    updateImages (state, images) {
      state.images = images
    },

    updateImageAtIndex (state, { index, image }) {
      state.images[index] = image
    }
  },

  actions: {
    addImageFromFile ({ state, commit }, payload) {
      const imageUrl = URL.createObjectURL(payload.file)

      // @todo handle metadata
      const metadata = payload.metadata ?? {}
      const id = 'eloquent-imagery-' + state.fieldName + '-' + (state.images.length + 1)

      // Object.keys(payload.metadata ?? [])
      // .map(key => ({ key, value: metadata[key] }))

      const image = {
        id,
        previewUrl: imageUrl,
        thumbnailUrl: imageUrl,
        metadata
      }

      const images = state.images
      images.push(image)

      commit('updateImages', images)

      return new Promise((resolve, reject) => {
        const reader = new FileReader()

        reader.addEventListener('load', () => {
          image.fileData = reader.result

          resolve(image)
        })

        reader.readAsDataURL(payload.file)
      })
    },

    removeImage ({ state, commit }, imageToRemove) {
      commit('updateImages', state.images.filter(image => image !== imageToRemove))
    },

    updateImageMetadata ({ state, commit }, payload) {
      const index = state.images.findIndex(image => image.id === payload.id)

      if (index < 0) {
        return
      }

      const image = state.images[index]
      image.metadata[payload.key] = payload.value

      commit('updateImageAtIndex', { index, image })

      return image.metadata
    }
  },

  getters: {
    getImages: (state) => state.images,

    getImageByPath: (state) => (imagePath) => {
      return state.images.find(image => image.path === imagePath)
    },

    getImageById: (state) => (imageId) => {
      return state.images.find(image => image.id === imageId)
    },

    getImageByMetadata: (state) => (name, value) => {
      return state.images.find(image => {
        return image.metadata[name] && image.metadata[name] === value
      })
    },

    getImageMetadata: (state) => (imageId, attribute) => {
      const foundImage = state.images.find(image => image.id === imageId)

      if (!foundImage) {
        return
      }

      if (!attribute) {
        return foundImage.metadata
      }

      return foundImage.metadata[attribute] ?? ''
    },

    serialize: (state) => {
      return state.images.map(image => {
        return {
          metadata: image.metadata,
          path: image.path ?? null,
          ...(image.fileData ? { fileData: image.fileData } : {})
        }
      })
    }
  }
}
