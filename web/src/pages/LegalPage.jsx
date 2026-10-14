export default function LegalPage({ title, children }) {
  return (
    <main className="page narrow">
      <h1>{title}</h1>
      <div className="prose">{children}</div>
    </main>
  )
}
