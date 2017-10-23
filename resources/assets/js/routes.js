import VueRouter from 'vue-router';

import Admin from './views/Admin';

import Dashboard from './views/Dashboard';
import SiteList from './views/SiteList';
import Media from './views/Media';
import Settings from './views/Settings'

import Editor from './views/Editor';
import MenuEditor from './views/MenuEditor';
import SiteUsers from './views/SiteUsers';
import Preview from './views/Preview';
import NotFound from './views/NotFound';
import Config from './classes/Config';

const routes = [
	{
		path: '/',
		component: Admin,
		redirect: '/home',
		children: [
			{
				path: 'home',
				component: Dashboard
			},
			{
				path: 'sites',
				component: SiteList
			},
			{
				path: 'media',
				component: Media
			},
			{
				path: 'settings',
				component: Settings
			}
		]
	},
	{
		path: '/site/:site_id',
		component: Editor,
		name: 'site'
	},
	{
		path: '/site/:site_id/page/:page_id',
		component: Editor,
		name: 'page'
	},
	{
		path: '/site/:site_id/menu',
		component: MenuEditor,
		name: 'menu-editor'
	},
	{
		path: '/site/:site_id/users',
		component: SiteUsers,
		name: 'site-users'
	},
	{
		path: '/preview/:page_id',
		component: Preview,
		name: 'preview'
	},
	{
		path: '*',
		component: NotFound,
		name: '404'
	}
];

export const router = new VueRouter({
	mode: 'history',
	routes,
	base: `${Config.get('base_url', '')}/`
});
