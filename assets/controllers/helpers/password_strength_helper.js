const testRegexp = (regexp) => (text) => regexp.test(text);
const isLongEnough = (text, passwordLength) => text.length >= passwordLength;

export const criteria = [
  { key: 'length', checker: isLongEnough },
  { key: 'special', checker: testRegexp(/(?=.*\W)/) },
  { key: 'number', checker: testRegexp(/\d/) },
  { key: 'lowercase', checker: testRegexp(/[a-z]/) },
  { key: 'uppercase', checker: testRegexp(/[A-Z]/) }
];

export const getValidCriteria = (inputValue, passwordLength) => criteria
  .filter(checkCriterion(inputValue, passwordLength))
  .map(criterion => criterion.key);

const checkCriterion = (inputValue, passwordLength) => (criterion) => criterion.checker(inputValue, passwordLength);

export const isPasswordStrongEnough = (validCriteria) => validCriteria.includes('length') && validCriteria.length >= 4;
