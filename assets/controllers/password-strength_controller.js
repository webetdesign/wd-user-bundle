import {Controller} from '@hotwired/stimulus';
import '../css/components/_password_strength.scss';

export default class extends Controller {
  static values = {
    isAdmin: {
      type: Boolean,
      default: false
    },
    minStrength: {
      type: Number,
      default: 2
    },
    iconShowPassword: {
      type: String
    },
    iconHidePassword: {
      type: String
    }
  };
  static targets = [
    'input',
    'inputIcon',
    'strengthLabel',
    'strengthVeryWeakMark',
    'strengthWeakMark',
    'strengthMediumMark',
    'strengthStrongMark',
    'strengthVeryStrongMark'
  ];

  strengthTarget = {
    STRENGTH_VERY_WEAK: 'strengthVeryWeakMarkTarget',
    STRENGTH_WEAK: 'strengthWeakMarkTarget',
    STRENGTH_MEDIUM: 'strengthMediumMarkTarget',
    STRENGTH_STRONG: 'strengthStrongMarkTarget',
    STRENGTH_VERY_STRONG: 'strengthVeryStrongMarkTarget'
  };
  strengthLabel = {
    STRENGTH_VERY_WEAK: 'Force du mot de passe très faible',
    STRENGTH_WEAK: 'Force du mot de passe faible',
    STRENGTH_MEDIUM: 'Force du mot de passe moyen',
    STRENGTH_STRONG: 'Force du mot de passe fort',
    STRENGTH_VERY_STRONG: 'Force du mot de passe très fort'
  };

  connect() {
  }

  inputChange(e) {
    const strength = this.calculatePasswordStrength(e.target.value);
    const strengths = Object.keys(this.strengthTarget);

    this.strengthLabelTarget.innerText = this.strengthLabel[strength];
    this.changeParentStatus(strength);
    if (!this.isAdminValue) {
      this.changeTextStatus(strength);
    }
    strengths.forEach(s => {
      const el = this[this.strengthTarget[s]];
      if (strengths.indexOf(s) <= strengths.indexOf(strength)) {
        el.classList.add(this.barColorClass(s));
      } else {
        el.classList.remove('line--valid');
        el.classList.remove('line--invalid');
      }
    });
  }

  calculatePasswordStrength(password) {
    let passwordCounts = this.countChars(password);
    let chars = Object.keys(passwordCounts).length;

    let control = 0, digit = 0, upper = 0, lower = 0, symbol = 0, other = 0;

    for (let chr in passwordCounts) {
      if (chr < 32 || chr === 127) control = 33;
      else if (chr >= 48 && chr <= 57) digit = 10;
      else if (chr >= 65 && chr <= 90) upper = 26;
      else if (chr >= 97 && chr <= 122) lower = 26;
      else if (chr >= 128) other = 128;
      else symbol = 33;
    }

    let pool = lower + upper + digit + symbol + control + other;
    let entropy = chars * Math.log2(pool) + (password.length - chars) * Math.log2(chars);

    if (entropy >= 120) return 'STRENGTH_VERY_STRONG';
    else if (entropy >= 100) return 'STRENGTH_STRONG';
    else if (entropy >= 80) return 'STRENGTH_MEDIUM';
    else if (entropy >= 60) return 'STRENGTH_WEAK';
    else return 'STRENGTH_VERY_WEAK';
  }

  countChars(str) {
    let charCounts = {};
    for (let i = 0; i < str.length; i++) {
      let char = str.charCodeAt(i);
      if (charCounts[char]) {
        charCounts[char]++;
      } else {
        charCounts[char] = 1;
      }
    }
    return charCounts;
  }

  isValid(strength) {
    const strengths = Object.keys(this.strengthTarget);

    return strengths.indexOf(strength) >= this.minStrengthValue;
  }

  barColorClass(strength) {
    return this.isValid(strength) ? 'line--valid' : 'line--invalid';
  }

  changeTextStatus(strength) {
    this.strengthLabelTarget.classList.remove('valid-feedback');
    this.strengthLabelTarget.classList.remove('invalid-feedback');
    this.strengthLabelTarget.classList.add(this.isValid(strength) ? 'valid-feedback' : 'invalid-feedback');
  }

  changeParentStatus(strength) {
    const validClass = this.isAdminValue ? 'has-success' : 'is-valid';
    const invalidClass = this.isAdminValue ? 'has-error' : 'is-invalid';

    this.inputTarget.parentNode.classList.remove(validClass);
    this.inputTarget.parentNode.classList.remove(invalidClass);
    this.inputTarget.parentNode.classList.add(this.isValid(strength) ? validClass : invalidClass);
  }

  toogleHideShow(e) {
    if (this.inputTarget.type === 'password') {
      this.inputTarget.type = 'text';
      this.inputIconTarget.classList.remove(...this.iconShowPasswordValue.split(' '));
      this.inputIconTarget.classList.add(...this.iconHidePasswordValue.split(' '));
    } else {
      this.inputTarget.type = 'password';
      this.inputIconTarget.classList.remove(...this.iconHidePasswordValue.split(' '));
      this.inputIconTarget.classList.add(...this.iconShowPasswordValue.split(' '));
    }
  }
}
