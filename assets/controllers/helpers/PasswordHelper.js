export default class PasswordHelper {
    lowerChars = 'abcdefghijklmnopqrstuvwxyz';
    upperChars = this.lowerChars.toUpperCase();
    numbers = '0123456789';

    constructor(length) {
        this.length = length;
        this.criteria = [
            { key: 'length', checker: this.isLongEnough },
            { key: 'lowercase', checker: this.testRegexp(/[a-z]/) },
            { key: 'uppercase', checker: this.testRegexp(/[A-Z]/) },
            { key: 'nonAlphabetic', checker: this.testRegexp(/.*[^A-Za-z].*/) }
        ];
    }

    getValidCriteria = inputValue => this.criteria
        .filter(this.checkCriterion(inputValue, this.length))
        .map(criterion => criterion.key);

    isPasswordStrongEnough = validCriteria => validCriteria.includes('length') && validCriteria.length >= 4;

    generate() {
        const passwordLength =  this.length;
        const upperCount = this.getRandomIntFromInterval(1, 2);
        const numberCount = this.getRandomIntFromInterval(1, 2);
        const lowerCount = passwordLength - upperCount - numberCount;

        const lowerPart = this.getGeneratedPasswordPart(lowerCount, this.lowerChars)
        const upperPart = this.getGeneratedPasswordPart(upperCount, this.upperChars)
        const numberPart = this.getGeneratedPasswordPart(numberCount, this.numbers)

        return (lowerPart+upperPart+numberPart).split('').sort(() => Math.random() - 0.5).join('');
    }

    testRegexp = regexp => text => regexp.test(text);

    isLongEnough = (text, passwordLength) => text.length >= passwordLength;

    checkCriterion = (inputValue, passwordLength) => criterion => criterion.checker(inputValue, passwordLength);

    getRandomIntFromInterval = (min, max) => Math.floor(Math.random() * (max - min + 1) + min);

    getGeneratedPasswordPart = (length, chars) => [...Array(length)].reduce((acc) => acc + this.getRandomChar(chars), '');

    getRandomChar = chars => chars.charAt(Math.floor(Math.random() * chars.length));
}
