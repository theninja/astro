<template>
	<div class="top-bar">
		<div class="top-bar__navigation">
			<slot name="back">
			<div
				v-if="(!showBack && this.$route.name !== 'site-list') && sites.length > 1"
			>
				<el-popover
					ref="site-picker"
					placement="bottom-start"
					v-model="siteDropdownVisible"
					transition="el-zoom-in-top"
				>
					<ul class="site-picker">
						<template v-for="(n, i) in Math.min(sites.length, 11)">
							<li v-if="n !== 11">
								<router-link :to="`/site/${sites[i].id}`" @click.native="siteDropdownVisible = false">
									{{ sites[i].name }}
								</router-link>
							</li>
							<li v-else>
								More sites available...
							</li>
						</template>

						<li>
							<router-link to="/" @click.native="siteDropdownVisible = false">
								<i class="el-icon-arrow-left"></i> Back to sites
							</router-link>
						</li>
					</ul>
				</el-popover>

				<span v-popover:site-picker class="site-pick">
					<icon name="site" />
					{{ siteTitle }}<i class="el-icon-caret-bottom el-icon--right"></i>
				</span>
			</div>
			<div
				v-if="(!showBack && this.$route.name !== 'site-list') && sites.length === 1"
				 class="site-pick"
			>
				<icon name="site" /> {{ siteTitle }}
			</div>

			<div v-show="showBack" @click="backToAdmin" class="top-bar-backbutton">
				<i class="el-icon-arrow-left backbutton-icon"></i>Back
			</div>

			</slot>

			<slot name="title" />

		</div>

		<el-button v-if='showInvalidateTokenButton' type="warning"
			@click="invalidateAPIToken"
		> Invalidate Token
		</el-button>

		<div class="top-bar__tools">
			<slot name="tools" v-if="showTools" />

			<el-popover
				ref="user-dropdown"
				placement="bottom-end"
				v-model="accountDropdownVisible"
				transition="el-zoom-in-top"
				popper-class="user-account-dropdown"
			>
				<div>
					<div class="user-account-dropdown__item">
						Signed in as <strong>{{ username }}</strong>
					</div>
					<div class="user-account-dropdown__item user-account-dropdown__item--divided">Settings</div>
					<div @click="signOut" class="user-account-dropdown__item user-account-dropdown__item--clickable">
						<form ref="submit-form" method="post" :action="`${config.get('logout_url', '?logout=1')}`">
							<input type="hidden" name="_token" :value="config.get('csrf_token')" />
						</form>
						<span>Sign out</span>
					</div>
				</div>
			</el-popover>

			<span v-popover:user-dropdown class="user-account-button">
				Account<i class="el-icon-caret-bottom el-icon--right"></i>
			</span>
		</div>
	</div>
</template>

<script>
import { mapActions, mapGetters, mapMutations } from 'vuex';
import Icon from 'components/Icon';
import promptToSave from 'mixins/promptToSaveMixin';
import Config from 'classes/Config.js';

/* global window */

export default {

	name: 'top-bar',

	props: ['site-id'],

	components: {
		Icon
	},

	mixins: [promptToSave],

	created() {
		this.fetchSiteData();
		// refresh our site list dropdown when a new site is added
		// TODO: replace with more structured state, rather than an event
		this.$bus.$on('top-bar:fetchSitData', this.fetchSiteData);
	},

	mounted() {
		this.loadPermissions();
		// this.loadGlobalRole(window.astro.username); jtw - moved to authiframe
		this.$store.dispatch('site/fetchLayouts');
		this.$store.dispatch('site/fetchSiteDefinitions');
	},

	destroyed() {
		this.$bus.$off('top-bar:fetchSitData');
	},

	watch: {
		'$route'() {
			this.$store.commit('resetCurrentSavedState');
			this.$store.commit('setLoaded', false);
			this.$store.commit('setPage', {});
			this.$store.commit('clearBlockErrors');
			this.$store.commit('updateMenuActive', 'pages');
			// TODO: prompt to save?
		}
	},

	data() {
		return {
			siteDropdownVisible: false,
			accountDropdownVisible: false,
			sites: []
		};
	},

	computed: {
		...mapGetters([
			'unsavedChangesExist'
		]),

		...mapGetters('auth', [
			'username'
		]),
		
		// works out if we should show a back button or not (ie whether we're editing a page or on the homepage)
		showBack() {
			return ['page', 'profile-editor'].indexOf(this.$route.name) !== -1;
		},

		showTools() {
			return ['site', 'page', 'profile-editor'].indexOf(this.$route.name) !== -1;
		},

		config() {
			return Config;
		},

		siteTitle() {
			const site = this.sites.find(site => site.id === Number(this.$route.params.site_id));
			return site ? site.name : '';
		},

		isUnsaved() {
			return this.unsavedChangesExist();
		}, 

		showInvalidateTokenButton() {
			return Config.get('debug');
		}
	},

	methods: {

		...mapActions([
			'loadPermissions'
			// 'loadGlobalRole' jtw moved to authiframe
		]),

		...mapMutations('auth', [
			'setAPIToken'
		]),

		invalidateAPIToken() {
			this.setAPIToken(null);
		},

		fetchSiteData() {
			// TODO: catch errors
			this.$api
				.get('sites')
				.then(({ data: json }) => {
					this.sites = json.data;
				});
		},

		signOut() {
			this.promptToSave({
				onConfirm: () => {
					this.$refs['submit-form'].submit();
				}
			});
		},

		/**
		 gets the user back to the main admin area
		 */
		backToAdmin() {
			this.promptToSave({
				onConfirm: () => {
					this.$router.push(`/site/${this.siteId || this.$route.params.site_id}`);
				}
			});
		}

	}
};
</script>
