<template>
<div v-if="value" class="image-field">
	<item-thumbnail
		:item="item"
		:enable-options="value.filename !== 'placeholder.jpg'"
		:on-edit="showMediaOverlay"
	>
		<div class="image-field__overlay">
			<el-button @click="showPicker">Change image</el-button>
		</div>
	</item-thumbnail>
</div>
<div v-else class="image-field__add-button">
	<el-button @click="showPicker">Add image</el-button>
</div>
</template>

<script>
import { mapMutations, mapActions } from 'vuex';

import BlockField from 'components/BlockField';
import MediaPicker from 'components/MediaPicker';
import ItemThumbnail from 'components/media/ItemThumbnail';

export default {

	name: 'image-field',

	extends: BlockField,

	components: {
		MediaPicker,
		ItemThumbnail
	},

	props: [
		'path',
		'accept',
		'multiple',
		'field'
	],

	computed: {
		item() {
			return { ...this.value, type: 'image' };
		}
	},

	methods: {
		...mapMutations([
			'setMediaType',
			'updateMediafieldPath',
			'showMediaPicker',
		]),

		...mapActions([
			'showMediaOverlay'
		]),

		showPicker() {
			this.setMediaType('image');
			this.updateMediafieldPath(this.path);
			this.showMediaPicker();
		}
	}
};
</script>
