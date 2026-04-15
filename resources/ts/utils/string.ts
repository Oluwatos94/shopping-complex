export function initials(name: string): string {
    return name.split(' ').slice(0, 2).map((n) => n.charAt(0)).join('').toUpperCase();
}
