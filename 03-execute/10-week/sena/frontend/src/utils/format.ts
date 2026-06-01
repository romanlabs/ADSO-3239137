export function statusClassName(value: string) {
  return value.toLowerCase().split(' ').join('-');
}
