const testRegexp = (regexp) => (text) => regexp.test(text);
const isEightChars = (text) => text.length >= 8

export const criteria = [
  { key: 'length', checker: isEightChars },
  { key: 'special', checker: testRegexp(/(?=.*\W)/) },
  { key: 'number', checker: testRegexp(/\d/) },
  { key: 'lowercase', checker: testRegexp(/[a-z]/) },
  { key: 'uppercase', checker: testRegexp(/[A-Z]/) },
]

export const getValidCriteria = (inputValue) => criteria
  .filter(checkCriterion(inputValue))
  .map(criterion => criterion.key)

const checkCriterion = (inputValue) => (criterion) => criterion.checker(inputValue)

export const isPasswordStrongEnough = (validCriteria) => validCriteria.includes('length') && validCriteria.length >= 4
