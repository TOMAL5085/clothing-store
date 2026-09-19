/** Price formatting for the storefront (USD now; currency system in Part 2/3). */
export function formatUSD(value: number): string {
  return `$${value.toFixed(2)}`;
}
