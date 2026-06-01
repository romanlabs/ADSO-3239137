interface StatCardProps {
  label: string;
  value: number;
  tone?: 'green' | 'blue' | 'amber' | 'red';
}

export function StatCard({ label, value, tone = 'green' }: StatCardProps) {
  return (
    <article className={`stat-card stat-card-${tone}`}>
      <span>{label}</span>
      <strong>{value}</strong>
    </article>
  );
}
