import './bootstrap';

import Alpine from 'alpinejs';
import moment from 'moment';
import 'moment/locale/id';

window.moment = moment;
window.Alpine = Alpine;

Alpine.start();
