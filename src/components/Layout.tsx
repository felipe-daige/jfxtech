import React from 'react';
import { Link } from 'react-router-dom';
import { ShoppingCart, Search, Menu, Cpu } from 'lucide-react';

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)]">
      {/* Top Bar - Technical Info */}
      <div className="bg-black text-white text-[10px] font-mono py-1 px-4 flex justify-between items-center tracking-widest uppercase opacity-90">
        <span>STATUS.SIS: ONLINE</span>
        <div className="flex gap-4">
          <span>LATÊNCIA: 12ms</span>
          <span>TEMP: 34°C</span>
        </div>
      </div>

      {/* Main Navigation */}
      <header className="sticky top-0 z-50 bg-[var(--color-lab-surface)]/80 backdrop-blur-md border-b border-[var(--color-lab-border)]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <Link to="/" className="flex items-center gap-2 group">
              <Cpu className="w-6 h-6 text-black group-hover:text-gray-600 transition-colors" />
              <span className="font-bold text-lg tracking-tight">JFXTECH</span>
            </Link>

            {/* Desktop Nav */}
            <nav className="hidden md:flex space-x-8">
              <Link to="/catalog" className="text-sm font-medium text-gray-900 hover:text-gray-500 transition-colors">COMPONENTES</Link>
              <Link to="/catalog" className="text-sm font-medium text-gray-900 hover:text-gray-500 transition-colors">PERIFÉRICOS</Link>
              <Link to="/catalog" className="text-sm font-medium text-gray-900 hover:text-gray-500 transition-colors">SISTEMAS</Link>
              <Link to="/catalog" className="text-sm font-medium text-gray-900 hover:text-gray-500 transition-colors">SUPORTE</Link>
            </nav>

            {/* Actions */}
            <div className="flex items-center space-x-4">
              <button className="p-2 text-gray-400 hover:text-black transition-colors">
                <Search className="w-5 h-5" />
              </button>
              <button className="p-2 text-gray-400 hover:text-black transition-colors relative">
                <ShoppingCart className="w-5 h-5" />
                <span className="absolute top-1 right-1 w-2 h-2 bg-black rounded-full"></span>
              </button>
              <button className="md:hidden p-2 text-gray-400 hover:text-black transition-colors">
                <Menu className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-grow">
        {children}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t border-[var(--color-lab-border)] py-12 mt-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Cpu className="w-5 h-5" />
              <span className="font-bold tracking-tight">JFXTECH</span>
            </div>
            <p className="text-sm text-gray-500 font-mono">Hardware gamer com engenharia de precisão para a elite.</p>
          </div>
          <div>
            <h4 className="font-mono text-xs font-bold uppercase tracking-widest mb-4">Produtos</h4>
            <ul className="space-y-2 text-sm text-gray-600">
              <li><Link to="/" className="hover:text-black">Mouses</Link></li>
              <li><Link to="/" className="hover:text-black">Teclados</Link></li>
              <li><Link to="/" className="hover:text-black">Headsets</Link></li>
              <li><Link to="/" className="hover:text-black">Monitores</Link></li>
            </ul>
          </div>
          <div>
            <h4 className="font-mono text-xs font-bold uppercase tracking-widest mb-4">Suporte</h4>
            <ul className="space-y-2 text-sm text-gray-600">
              <li><Link to="/" className="hover:text-black">Downloads</Link></li>
              <li><Link to="/" className="hover:text-black">Garantia</Link></li>
              <li><Link to="/" className="hover:text-black">Fale Conosco</Link></li>
            </ul>
          </div>
          <div>
            <h4 className="font-mono text-xs font-bold uppercase tracking-widest mb-4">Newsletter</h4>
            <div className="flex">
              <input type="email" placeholder="DIGITE SEU E-MAIL" className="bg-gray-50 border border-gray-200 px-3 py-2 text-sm w-full focus:outline-none focus:border-black font-mono" />
              <button className="bg-black text-white px-4 py-2 text-sm font-bold hover:bg-gray-800 transition-colors">
                &rarr;
              </button>
            </div>
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 pt-8 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center text-xs text-gray-400 font-mono">
          <p>&copy; 2026 JFXTECH. TODOS OS DIREITOS RESERVADOS.</p>
          <div className="flex space-x-4 mt-4 md:mt-0">
            <Link to="/" className="hover:text-gray-900">TERMOS</Link>
            <Link to="/" className="hover:text-gray-900">PRIVACIDADE</Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
